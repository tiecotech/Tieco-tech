<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProjectRequest;
use App\Models\ProjectRequestAttachment;
use App\Models\ProjectType;
use App\Models\RequestPayment;
use App\Models\RequestStatus;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'type', 'from', 'to', 'deal', 'source']);
        $page = $this->filtered($request)->with(['status', 'assignee:id,name'])->withSum('payments as received_minor', 'amount_minor')
            ->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/requests/index', [
            'requests' => [
                'data' => collect($page->items())->map(fn (ProjectRequest $r) => [
                    'id' => $r->id, 'number' => $r->number, 'date' => $r->created_at?->format('Y-m-d H:i'),
                    'customer' => $r->customer_name, 'phone' => $r->phone, 'type' => $r->project_type_snapshot['name'] ?? [],
                    'total_minor' => $r->total_minor, 'has_unpriced_items' => $r->has_unpriced_items,
                    'status' => ['name' => $r->status->tr('name'), 'color' => $r->status->color],
                    'assignee' => $r->assignee?->name, 'source' => $r->source ?? 'website',
                    'sold_at' => $r->sold_at?->toDateString(), 'final_price_minor' => $r->final_price_minor,
                    'received_minor' => (int) ($r->received_minor ?? 0),
                ])->values(),
                'links' => $page->linkCollection()->map(fn ($l) => ['url' => $l['url'], 'label' => strip_tags((string) $l['label']), 'active' => $l['active']])->values(),
                'total' => $page->total(),
            ],
            'filters' => $filters,
            'statuses' => RequestStatus::orderBy('sort_order')->get()->map(fn ($s) => ['key' => $s->key, 'name' => $s->tr('name')])->values(),
            'types' => ProjectType::orderBy('sort_order')->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->tr('name')])->values(),
        ]);
    }

    public function show(ProjectRequest $projectRequest): Response
    {
        $r = $projectRequest->load(['status', 'assignee:id,name', 'items', 'attachments', 'notes.author:id,name', 'payments.author:id,name', 'seller:id,name']);
        $latestQuote = $r->quotes()->whereIn('status', ['sent', 'accepted'])->first();

        return Inertia::render('admin/requests/show', [
            'request' => [
                'id' => $r->id, 'number' => $r->number, 'source' => $r->source ?? 'website',
                'sold_at' => $r->sold_at?->toDateString(), 'final_price_minor' => $r->final_price_minor, 'seller' => $r->seller?->name,
                'received_minor' => $r->receivedMinor(), 'quote_total_minor' => $latestQuote?->total_minor,
                'payments' => $r->payments->map(fn ($p) => [
                    'id' => $p->id, 'amount_minor' => $p->amount_minor, 'paid_at' => $p->paid_at?->toDateString(),
                    'method' => $p->method, 'note' => $p->note, 'author' => $p->author?->name,
                ])->values(),
                'locale' => $r->locale, 'submitted_at' => $r->created_at?->format('Y-m-d H:i'),
                'type' => $r->project_type_snapshot['name'] ?? [], 'pages' => $r->pages,
                'status_id' => $r->request_status_id, 'assigned_to' => $r->assigned_to,
                'customer_name' => $r->customer_name, 'phone' => $r->phone, 'email' => $r->email, 'country' => $r->country,
                'preferred_contact' => $r->preferred_contact, 'whatsapp_opt_in' => $r->whatsapp_opt_in,
                'description' => $r->description, 'goal' => $r->goal, 'audience' => $r->audience,
                'expected_date' => $r->expected_date?->toDateString(), 'budget_minor' => $r->budget_minor,
                'currency' => $r->currency, 'subtotal_minor' => $r->subtotal_minor, 'discount_minor' => $r->discount_minor,
                'total_minor' => $r->total_minor, 'has_unpriced_items' => $r->has_unpriced_items,
                'items' => $r->items->map(fn ($i) => [
                    'id' => $i->id, 'kind' => $i->kind, 'name' => $i->name, 'quantity' => $i->quantity,
                    'unit_price_minor' => $i->unit_price_minor, 'line_total_minor' => $i->line_total_minor,
                ])->values(),
                'attachments' => $r->attachments->map(fn ($a) => [
                    'id' => $a->id, 'name' => $a->original_name, 'size' => $a->size, 'mime' => $a->mime,
                ])->values(),
                'notes' => $r->notes->map(fn ($n) => [
                    'id' => $n->id, 'type' => $n->type, 'channel' => $n->channel, 'body' => $n->body,
                    'author' => $n->author?->name, 'at' => $n->created_at?->format('Y-m-d H:i'),
                ])->values(),
            ],
            'statuses' => RequestStatus::orderBy('sort_order')->get()->map(fn ($s) => ['id' => $s->id, 'key' => $s->key, 'name' => $s->tr('name'), 'color' => $s->color])->values(),
            'staff' => User::where('is_active', true)->orderBy('name')->get()
                ->filter(fn (User $u) => $u->hasPermission('requests.manage'))
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
        ]);
    }

    public function status(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $data = $request->validate([
            'request_status_id' => ['required', 'integer', 'exists:request_statuses,id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $old = $projectRequest->status;
        $new = RequestStatus::findOrFail($data['request_status_id']);
        if ($old->id === $new->id) {
            return back();
        }

        DB::transaction(function () use ($projectRequest, $old, $new, $data, $request) {
            $projectRequest->update(['request_status_id' => $new->id]);
            $projectRequest->notes()->create([
                'user_id' => $request->user()->id, 'type' => 'status',
                'body' => $old->tr('name').' → '.$new->tr('name').(filled($data['note'] ?? null) ? "\n".$data['note'] : ''),
            ]);
            ActivityLog::record('request.status_changed', $projectRequest, ['from' => $old->key, 'to' => $new->key]);
        });

        return back();
    }

    public function assign(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        if ($data['assigned_to'] ?? null) {
            abort_unless(User::findOrFail($data['assigned_to'])->hasPermission('requests.manage'), 422);
        }

        $before = $projectRequest->assigned_to;
        $projectRequest->update(['assigned_to' => $data['assigned_to'] ?? null]);
        ActivityLog::record('request.assigned', $projectRequest, ['from' => $before, 'to' => $data['assigned_to'] ?? null]);

        return back();
    }

    public function note(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:note,contact'],
            'channel' => ['nullable', 'in:phone,whatsapp,email,meeting'],
            'body' => ['required', 'string', 'max:3000'],
        ]);
        $projectRequest->notes()->create($data + ['user_id' => $request->user()->id]);

        return back();
    }

    // ---- manual deals (sold outside the website) and sales tracking ----
    public function create(): Response
    {
        return Inertia::render('admin/requests/create', [
            'statuses' => RequestStatus::orderBy('sort_order')->get()->map(fn ($s) => ['id' => $s->id, 'key' => $s->key, 'name' => $s->tr('name')])->values(),
            'types' => ProjectType::orderBy('sort_order')->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->tr('name')])->values(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'country' => ['nullable', 'string', 'max:80'],
            'project_type_id' => ['nullable', 'integer', 'exists:project_types,id'],
            'title' => ['nullable', 'required_without:project_type_id', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'request_status_id' => ['required', 'integer', 'exists:request_statuses,id'],
            'final_price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'sold_at' => ['nullable', 'date', 'required_with:final_price'],
            'paid_amount' => ['nullable', 'numeric', 'min:0.01', 'max:100000000'],
            'paid_at' => ['nullable', 'date', 'required_with:paid_amount'],
            'method' => ['nullable', 'in:cash,transfer,card,other'],
        ]);

        $type = isset($data['project_type_id']) ? ProjectType::find($data['project_type_id']) : null;
        $name = filled($data['title'] ?? null) ? ['en' => $data['title'], 'ar' => $data['title']] : (array) $type?->name;
        $final = isset($data['final_price']) ? $this->toMinor($data['final_price']) : null;
        $currency = $this->currency();

        $r = DB::transaction(function () use ($data, $type, $name, $final, $currency, $request) {
            $r = ProjectRequest::create([
                'number' => ProjectRequest::generateNumber(), 'source' => 'manual', 'locale' => app()->getLocale(),
                'project_type_id' => $type?->id, 'project_type_snapshot' => ['key' => $type?->key, 'name' => $name],
                'request_status_id' => $data['request_status_id'], 'assigned_to' => $request->user()->id,
                'customer_name' => $data['customer_name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null,
                'country' => $data['country'] ?? null, 'description' => $data['description'] ?? null,
                'currency' => $currency['code'], 'subtotal_minor' => $final ?? 0, 'total_minor' => $final ?? 0,
                'pricing_snapshot' => ['source' => 'manual'],
                'sold_at' => $final !== null ? $data['sold_at'] : null, 'final_price_minor' => $final,
                'sold_by' => $final !== null ? $request->user()->id : null,
            ]);
            if (isset($data['paid_amount'])) {
                $r->payments()->create([
                    'amount_minor' => $this->toMinor($data['paid_amount']), 'paid_at' => $data['paid_at'],
                    'method' => $data['method'] ?? 'cash', 'created_by' => $request->user()->id,
                ]);
            }
            $r->notes()->create(['user_id' => $request->user()->id, 'type' => 'note', 'body' => __('admin_sales.s.note_manual')]);
            ActivityLog::record('request.manual_created', $r, ['number' => $r->number, 'final_price_minor' => $final]);

            return $r;
        });

        return redirect()->route('admin.requests.show', $r);
    }

    /** Record, update or clear the closed deal: sale date and final agreed price. */
    public function sale(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $data = $request->validate([
            'sold_at' => ['nullable', 'date', 'required_with:final_price'],
            'final_price' => ['nullable', 'numeric', 'min:0', 'max:100000000', 'required_with:sold_at'],
        ]);
        $final = isset($data['final_price']) ? $this->toMinor($data['final_price']) : null;
        $before = ['sold_at' => $projectRequest->sold_at?->toDateString(), 'final_price_minor' => $projectRequest->final_price_minor];

        $projectRequest->update([
            'sold_at' => $final !== null ? $data['sold_at'] : null,
            'final_price_minor' => $final,
            'sold_by' => $final !== null ? ($projectRequest->sold_by ?? $request->user()->id) : null,
        ]);
        $projectRequest->notes()->create([
            'user_id' => $request->user()->id, 'type' => 'note',
            'body' => $final !== null
                ? __('admin_sales.s.note_sold', ['date' => $data['sold_at'], 'amount' => $this->fmt($final)])
                : __('admin_sales.s.note_unsold'),
        ]);
        ActivityLog::record('request.sale_recorded', $projectRequest, ['before' => $before, 'final_price_minor' => $final]);

        return back();
    }

    public function paymentStore(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'in:cash,transfer,card,other'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $amount = $this->toMinor($data['amount']);
        $projectRequest->payments()->create([
            'amount_minor' => $amount, 'paid_at' => $data['paid_at'], 'method' => $data['method'],
            'note' => $data['note'] ?? null, 'created_by' => $request->user()->id,
        ]);
        $projectRequest->notes()->create([
            'user_id' => $request->user()->id, 'type' => 'note',
            'body' => __('admin_sales.s.note_paid', ['amount' => $this->fmt($amount), 'date' => $data['paid_at']]),
        ]);
        ActivityLog::record('request.payment_added', $projectRequest, ['amount_minor' => $amount]);

        return back();
    }

    public function paymentDestroy(ProjectRequest $projectRequest, RequestPayment $payment): RedirectResponse
    {
        abort_unless($payment->project_request_id === $projectRequest->id, 404);
        ActivityLog::record('request.payment_deleted', $projectRequest, ['amount_minor' => $payment->amount_minor, 'paid_at' => $payment->paid_at?->toDateString()]);
        $payment->delete();

        return back();
    }

    /** @return array{code: string, decimals: int} */
    private function currency(): array
    {
        $c = (array) (SiteSetting::get('currency') ?? []);

        return ['code' => (string) ($c['code'] ?? 'USD'), 'decimals' => (int) ($c['decimals'] ?? 2)];
    }

    /** Major units typed by staff (e.g. 1500.50) to integer minor units. */
    private function toMinor(mixed $value): int
    {
        return (int) round(((float) $value) * (10 ** $this->currency()['decimals']));
    }

    private function fmt(int $minor): string
    {
        $c = $this->currency();

        return number_format($minor / (10 ** $c['decimals']), $c['decimals']).' '.$c['code'];
    }

    public function download(ProjectRequest $projectRequest, ProjectRequestAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->project_request_id === $projectRequest->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
        ActivityLog::record('request.attachment_downloaded', $projectRequest, ['file' => $attachment->original_name]);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function export(Request $request): StreamedResponse
    {
        ActivityLog::record('request.exported', null, $request->only(['q', 'status', 'type', 'from', 'to']));
        $query = $this->filtered($request)->with('status')->withSum('payments as received_minor', 'amount_minor')->latest();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Arabic correctly
            fputcsv($out, ['Number', 'Date', 'Customer', 'Phone', 'Email', 'Project type', 'Status', 'Pages', 'Subtotal', 'Discount', 'Total', 'Currency', 'Source', 'Sold on', 'Final price', 'Received', 'Remaining']);
            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map([$this, 'safeCell'], [
                        $r->number, $r->created_at?->format('Y-m-d H:i'), $r->customer_name, $r->phone, $r->email,
                        $r->project_type_snapshot['name']['en'] ?? '', $r->status->key, $r->pages,
                        number_format($r->subtotal_minor / 100, 2, '.', ''), number_format($r->discount_minor / 100, 2, '.', ''),
                        number_format($r->total_minor / 100, 2, '.', ''), $r->currency,
                        $r->source ?? 'website', $r->sold_at?->toDateString(),
                        $r->final_price_minor === null ? '' : number_format($r->final_price_minor / 100, 2, '.', ''),
                        number_format(((int) $r->received_minor) / 100, 2, '.', ''),
                        $r->final_price_minor === null ? '' : number_format(($r->final_price_minor - (int) $r->received_minor) / 100, 2, '.', ''),
                    ]));
                }
            });
            fclose($out);
        }, 'tieco-requests-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralises spreadsheet formula injection in exported text. */
    public function safeCell(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    private function filtered(Request $request): Builder
    {
        $q = ProjectRequest::query();
        if ($s = trim((string) $request->query('q'))) {
            $like = '%'.addcslashes($s, '%_\\').'%';
            $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('customer_name', 'like', $like)
                ->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like));
        }
        if ($request->filled('status')) {
            $q->whereHas('status', fn ($w) => $w->where('key', $request->query('status')));
        }
        if ($request->filled('type')) {
            $q->where('project_type_id', (int) $request->query('type'));
        }
        if ($request->filled('source')) {
            $q->where('source', $request->query('source') === 'manual' ? 'manual' : 'website');
        }
        // deal: sold | unsold | due (sold but not fully collected)
        match ($request->query('deal')) {
            'sold' => $q->whereNotNull('sold_at'),
            'unsold' => $q->whereNull('sold_at'),
            'due' => $q->whereNotNull('sold_at')->whereRaw(
                'final_price_minor > (SELECT COALESCE(SUM(amount_minor), 0) FROM request_payments WHERE request_payments.project_request_id = project_requests.id)'
            ),
            default => null,
        };
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->query('to'));
        }

        return $q;
    }
}
