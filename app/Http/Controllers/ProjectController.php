<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::public()->with('category')->get()->map->card()->values();

        return Inertia::render('projects', ['projects' => $projects, 'services' => $this->services()]);
    }

    public function show(string $slug): Response
    {
        $project = Project::public()->where('slug', $slug)->with(['category', 'images'])->firstOrFail();
        $others = Project::public()->where('id', '!=', $project->id)->with('category')->get()->map->card()->values();

        return Inertia::render('project', [
            'project' => $project->detail(), 'others' => $others, 'services' => $this->services(),
        ]);
    }

    /** Footer links. */
    private function services(): array
    {
        return Service::visible()->get()->map(fn (Service $s) => ['id' => $s->id, 'slug' => $s->slug, 'name' => $s->tr('name')])->values()->all();
    }
}
