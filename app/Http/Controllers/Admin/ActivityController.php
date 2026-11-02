<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $logs = ActivityLog::with('user:id,name')
            ->when($q !== '', fn ($x) => $x->where('action', 'like', "%{$q}%"))
            ->latest('id')->paginate(30)->withQueryString()
            ->through(fn (ActivityLog $l) => [
                'id' => $l->id, 'action' => $l->action, 'user' => $l->user?->name, 'ip' => $l->ip,
                'subject' => $l->subject_type ? class_basename($l->subject_type).' #'.$l->subject_id : null,
                'details' => $l->properties ? json_encode($l->properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'at' => $l->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('admin/activity', ['logs' => $logs, 'filters' => ['q' => $q]]);
    }
}
