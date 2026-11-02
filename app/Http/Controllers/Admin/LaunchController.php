<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Support\Launch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Website launch switch: coming-soon page vs. the real site, plus the private preview link. */
class LaunchController extends Controller
{
    public function show(): Response
    {
        $s = Launch::settings();

        return Inertia::render('admin/launch', [
            'live' => $s['live'],
            'forced' => (bool) config('tieco.force_live'),
            'date' => $s['date'] ?? '',
            'previewUrl' => Launch::previewUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'live' => ['required', 'boolean'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $before = Launch::settings();
        SiteSetting::put('launch', ['live' => (bool) $data['live'], 'date' => $data['date'] ?? null] + $before);

        if ($before['live'] !== (bool) $data['live']) {
            ActivityLog::record($data['live'] ? 'site.launched' : 'site.unlaunched');
        }

        return back();
    }

    public function rotate(): RedirectResponse
    {
        Launch::rotateToken();
        ActivityLog::record('site.preview_link_rotated');

        return back();
    }
}
