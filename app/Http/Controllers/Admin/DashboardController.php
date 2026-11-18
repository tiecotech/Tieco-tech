<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProjectRequest;
use App\Models\RequestPayment;
use App\Models\RequestStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $counts = ProjectRequest::selectRaw('request_status_id, COUNT(*) as c')->groupBy('request_status_id')->pluck('c', 'request_status_id');
        $statuses = RequestStatus::orderBy('sort_order')->get()->map(fn ($s) => [
            'key' => $s->key, 'name' => $s->tr('name'), 'color' => $s->color, 'count' => (int) ($counts[$s->id] ?? 0),
        ])->values();
        $byKey = $statuses->keyBy('key');

        $from = now()->subDays(13)->startOfDay();
        $rows = ProjectRequest::where('created_at', '>=', $from)->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $series = collect(range(0, 13))->map(function ($i) use ($from, $rows) {
            $day = $from->copy()->addDays($i)->toDateString();

            return ['date' => $day, 'count' => (int) ($rows[$day] ?? 0)];
        })->values();

        return Inertia::render('admin/dashboard', [
            'kpis' => [
                'total' => (int) $counts->sum(),
                'new' => (int) ($byKey['new']['count'] ?? 0),
                'in_review' => (int) ($byKey['in_review']['count'] ?? 0),
                'quote_sent' => (int) ($byKey['quote_sent']['count'] ?? 0),
                'accepted' => (int) ($byKey['accepted']['count'] ?? 0),
                'rejected' => (int) ($byKey['rejected']['count'] ?? 0),
            ],
            'statuses' => $statuses,
            'series' => $series,
            'sales' => $this->sales(),
            'latest' => ProjectRequest::with('status')->latest()->limit(8)->get()->map(fn ($r) => $this->row($r))->values(),
            'activity' => $request->user()->hasPermission('*')
                ? ActivityLog::with('user:id,name')->latest()->limit(8)->get()->map(fn ($a) => [
                    'id' => $a->id, 'action' => $a->action, 'user' => $a->user?->name, 'at' => $a->created_at?->diffForHumans(),
                ])->values()
                : [],
        ]);
    }

    /** Business overview: what was sold, what came in, what is still expected. */
    private function sales(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $sold = ProjectRequest::whereNotNull('sold_at');
        $soldTotal = (int) (clone $sold)->sum('final_price_minor');

        $due = ProjectRequest::whereNotNull('sold_at')->withSum('payments as received_minor', 'amount_minor')->orderBy('sold_at')->get()
            ->map(fn ($r) => ['id' => $r->id, 'number' => $r->number, 'customer' => $r->customer_name, 'sold_at' => $r->sold_at?->toDateString(),
                'final_minor' => (int) $r->final_price_minor, 'received_minor' => (int) $r->received_minor,
                'remaining_minor' => (int) $r->final_price_minor - (int) $r->received_minor])
            ->filter(fn ($r) => $r['remaining_minor'] > 0)->values();

        $months = collect(range(5, 0))->map(function ($i) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = $start->copy()->endOfMonth();

            return [
                'month' => $start->format('Y-m'),
                'sold_minor' => (int) ProjectRequest::whereBetween('sold_at', [$start->toDateString(), $end->toDateString()])->sum('final_price_minor'),
                'received_minor' => (int) RequestPayment::whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->sum('amount_minor'),
                'deals' => ProjectRequest::whereBetween('sold_at', [$start->toDateString(), $end->toDateString()])->count(),
            ];
        })->values();

        return [
            'deals_total' => (clone $sold)->count(),
            'sold_total_minor' => $soldTotal,
            'received_total_minor' => (int) RequestPayment::sum('amount_minor'),
            'expected_minor' => (int) $due->sum('remaining_minor'),
            'month_deals' => (clone $sold)->where('sold_at', '>=', $monthStart)->count(),
            'month_sold_minor' => (int) (clone $sold)->where('sold_at', '>=', $monthStart)->sum('final_price_minor'),
            'month_received_minor' => (int) RequestPayment::where('paid_at', '>=', $monthStart)->sum('amount_minor'),
            'months' => $months,
            'due' => $due->sortByDesc('remaining_minor')->take(8)->values(),
        ];
    }

    private function row(ProjectRequest $r): array
    {
        return [
            'id' => $r->id, 'number' => $r->number, 'customer' => $r->customer_name,
            'type' => $r->project_type_snapshot['name'] ?? [], 'total_minor' => $r->total_minor,
            'status' => ['name' => $r->status->tr('name'), 'color' => $r->status->color],
            'at' => $r->created_at?->diffForHumans(),
        ];
    }
}
