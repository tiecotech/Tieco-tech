<?php

namespace App\Services\ProjectRequests;

use App\Models\ActivityLog;
use App\Models\ProjectRequest;
use App\Models\ProjectType;
use App\Models\RequestStatus;
use App\Models\ServiceOption;
use App\Models\User;
use App\Notifications\NewProjectRequestNotification;
use App\Notifications\ProjectRequestReceivedNotification;
use App\Services\Pricing\PricingCalculator;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\PricingInput;
use App\Support\Mailing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProjectRequestSubmitter
{
    public function __construct(private PricingCalculator $calculator) {}

    /**
     * Re-prices everything on the server, saves the request, items, price snapshot and files in one
     * transaction, and only then notifies. Throws ValidationException when selections are invalid.
     *
     * @param  array<string,mixed>  $data  validated customer data
     * @param  list<UploadedFile>  $files
     */
    public function submit(array $data, array $files, string $locale): ProjectRequest
    {
        $phone = PricingCalculator::normalizePhone($data['phone'] ?? null);
        $input = new PricingInput(
            projectTypeId: (int) $data['project_type_id'],
            pages: isset($data['pages']) ? (int) $data['pages'] : null,
            options: (array) ($data['options'] ?? []),
            phone: $data['phone'] ?? null,
            whatsappOptIn: (bool) ($data['whatsapp_opt_in'] ?? false),
            attachmentCount: count($files),
        );

        try {
            $pricing = $this->calculator->calculate($input);
        } catch (PricingException $e) {
            throw ValidationException::withMessages($e->errors);
        }

        $type = ProjectType::findOrFail($input->projectTypeId);
        if ($type->kind === 'mobile' && ! ServiceOption::whereIn('id', array_keys($input->options))->where('group', 'platform')->exists()) {
            throw ValidationException::withMessages(['options' => 'Choose at least one platform.']);
        }

        $disk = config('tieco.uploads.disk');
        $stored = [];

        try {
            $request = DB::transaction(function () use ($data, $files, $locale, $pricing, $type, $phone, $disk, &$stored) {
                $request = ProjectRequest::create([
                    'number' => ProjectRequest::generateNumber(),
                    'locale' => $locale,
                    'project_type_id' => $type->id,
                    'project_type_snapshot' => ['key' => $type->key, 'kind' => $type->kind, 'name' => $type->name],
                    'request_status_id' => RequestStatus::default()->id,
                    'customer_name' => $data['customer_name'],
                    'phone' => $phone ?? ($data['phone'] ?? null),
                    'email' => $data['email'] ?? null,
                    'country' => $data['country'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'preferred_contact' => $data['preferred_contact'] ?? null,
                    'whatsapp_opt_in' => (bool) ($data['whatsapp_opt_in'] ?? false) && $phone !== null,
                    'description' => $data['description'] ?? null,
                    'goal' => $data['goal'] ?? null,
                    'audience' => $data['audience'] ?? null,
                    'expected_date' => $data['expected_date'] ?? null,
                    'budget_minor' => isset($data['budget']) && $data['budget'] !== '' ? (int) round(((float) $data['budget']) * 100) : null,
                    'pages' => $pricing['pages'],
                    'currency' => $pricing['currency'],
                    'subtotal_minor' => $pricing['subtotal_minor'],
                    'discount_minor' => $pricing['discount_minor'],
                    'total_minor' => $pricing['total_minor'],
                    'has_unpriced_items' => $pricing['has_unpriced_items'],
                    'pricing_snapshot' => $pricing + ['calculated_at' => now()->toIso8601String()],
                ]);

                foreach ($pricing['lines'] as $line) {
                    $request->items()->create([
                        'service_option_id' => $line['option_id'] ?? null,
                        'kind' => $line['kind'], 'code' => $line['code'], 'name' => $line['name'],
                        'quantity' => $line['quantity'], 'unit_price_minor' => $line['unit_price_minor'],
                        'line_total_minor' => $line['line_total_minor'], 'meta' => $line['meta'] ?: null,
                    ]);
                }
                foreach ($pricing['discounts'] as $d) {
                    $request->items()->create([
                        'kind' => 'discount', 'code' => $d['code'], 'name' => $d['name'], 'quantity' => 1,
                        'unit_price_minor' => $d['amount_minor'], 'line_total_minor' => -$d['amount_minor'],
                        'meta' => ['rule_id' => $d['rule_id'], 'type' => $d['type'], 'value' => $d['value']],
                    ]);
                }

                foreach ($files as $file) {
                    $name = Str::uuid()->toString().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                    $path = $file->storeAs('requests/'.now()->format('Y/m'), $name, $disk);
                    if ($path === false) {
                        throw new \RuntimeException('Could not store an attachment.');
                    }
                    $stored[] = $path;
                    $request->attachments()->create([
                        'disk' => $disk, 'path' => $path,
                        'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
                        'mime' => (string) $file->getMimeType(), 'size' => $file->getSize(),
                        'sha256' => hash_file('sha256', $file->getRealPath()),
                    ]);
                }

                ActivityLog::record('request.submitted', $request, ['number' => $request->number, 'total_minor' => $request->total_minor]);

                return $request;
            });
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        }

        $this->notify($request, $locale);

        return $request;
    }

    /** Notification failures must never undo a saved request. */
    private function notify(ProjectRequest $request, string $locale): void
    {
        try {
            $staff = User::where('is_active', true)->get()->filter(fn (User $u) => $u->hasPermission('requests.manage'));
            Mailing::alertStaff($staff, new NewProjectRequestNotification($request));

            if ($request->email && Mailing::isConfigured()) {
                Notification::route('mail', $request->email)->notify(new ProjectRequestReceivedNotification($request, $locale));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
