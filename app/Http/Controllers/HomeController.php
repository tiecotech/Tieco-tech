<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Service;
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
        $projects = Project::public()->featured()->with('category')->limit(6)->get()->map->card()->values();

        return Inertia::render('home', ['services' => $services, 'projects' => $projects]);
    }
}
