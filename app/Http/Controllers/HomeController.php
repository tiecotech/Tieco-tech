<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $services = Service::visible()->get()->map(fn (Service $s) => [
            'id' => $s->id, 'slug' => $s->slug, 'icon' => $s->icon,
            'name' => $s->tr('name'), 'summary' => $s->tr('summary'),
        ])->values();

        // Demo projects are never shown on the public site.
        $projects = Project::visible()->featured()->where('is_demo', false)
            ->with('category')->limit(6)->get()->map(fn (Project $p) => [
                'id' => $p->id, 'slug' => $p->slug,
                'title' => $p->tr('title'), 'summary' => $p->tr('summary'),
                'category' => $p->category?->tr('name'),
                'cover' => $p->cover_path ? Storage::disk('public')->url($p->cover_path) : null,
            ])->values();

        return Inertia::render('home', ['services' => $services, 'projects' => $projects]);
    }
}
