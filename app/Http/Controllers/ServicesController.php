<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class ServicesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('services', [
            'services' => Service::visible()->get()->map(fn (Service $s) => $this->card($s))->values(),
        ]);
    }

    public function show(string $slug): Response
    {
        $service = Service::visible()->where('slug', $slug)->firstOrFail();

        $list = fn (string $attr): array => collect((array) $service->tr($attr))->filter(fn ($v) => is_string($v) && trim($v) !== '')->values()->all();
        $rows = fn (string $attr, array $keys): array => collect((array) $service->tr($attr))
            ->filter(fn ($r) => is_array($r) && collect($keys)->every(fn ($k) => filled($r[$k] ?? null)))
            ->map(fn ($r) => collect($keys)->mapWithKeys(fn ($k) => [$k => (string) $r[$k]])->all())->values()->all();

        return Inertia::render('service-show', [
            'service' => $this->card($service) + [
                'description' => $service->tr('description'),
                'problems' => $list('problems'),
                'deliverables' => $list('deliverables'),
                'features' => $list('features'),
                'steps' => $rows('steps', ['title', 'text']),
                'faqs' => $rows('faqs', ['q', 'a']),
            ],
            'others' => Service::visible()->where('id', '!=', $service->id)->get()->map(fn (Service $s) => $this->card($s))->values(),
        ]);
    }

    private function card(Service $s): array
    {
        return ['id' => $s->id, 'slug' => $s->slug, 'icon' => $s->icon, 'name' => $s->tr('name'), 'summary' => $s->tr('summary')];
    }
}
