<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProjectRequest;
use App\Models\ProjectRequestAttachment;
use App\Models\ProjectType;
use App\Models\RequestStatus;
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
        $filters = $request->only(['q', 'status', 'type', 'from', 'to']);
        $page = $this->filtered($request)->with(['status', 'assignee:id,name'])->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/requests/index', [
            'requests' => [
                'data' => collect($page->items())->map(fn (ProjectRequest $r) => [
                    'id' => $r->id, 'number' => $r->number, 'date' => $r->created_at?->format('Y-m-d H:i'),
                    'customer' => $r->customer_name, 'phone' => $r->phone, 'type' => $r->project_type_snapshot['name'] ?? [],
                    'total_minor' => $r->total_minor, 'has_unpriced_items' => $r->has_unpriced_items,
                    'status' => ['name' => $r->status->tr('name'), 'color' => $r->status->color],
                    'assignee' => $r->assignee?->name,
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
        $r = $projectRequest->load(['status', 'assignee:id,name', 'items', 'attachments', 'notes.author:id,name']);

        return Inertia::render('admin/requests/show', [
            'request' => [
                'id' => $r->id, 'number' => $r->number, 'locale' => $r->locale, 'submitted_at' => $r->created_at?->format('Y-m-d H:i'),
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
        $query = $this->filtered($request)->with('status')->latest();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Arabic correctly
            fputcsv($out, ['Number', 'Date', 'Customer', 'Phone', 'Email', 'Project type', 'Status', 'Pages', 'Subtotal', 'Discount', 'Total', 'Currency']);
            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map([$this, 'safeCell'], [
                        $r->number, $r->created_at?->format('Y-m-d H:i'), $r->customer_name, $r->phone, $r->email,
                        $r->project_type_snapshot['name']['en'] ?? '', $r->status->key, $r->pages,
                        number_format($r->subtotal_minor / 100, 2, '.', ''), number_format($r->discount_minor / 100, 2, '.', ''),
                        number_format($r->total_minor / 100, 2, '.', ''), $r->currency,
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
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->query('to'));
        }

        return $q;
    }
}
