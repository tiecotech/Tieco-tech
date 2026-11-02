<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public const LEGAL = ['privacy', 'terms', 'requests-policy'];

    public function about(): Response
    {
        $page = Page::where('slug', 'about')->where('is_published', true)->first();

        return Inertia::render('about', [
            // Custom text written in the admin panel replaces the built-in intro.
            'custom' => $page ? ['title' => $page->tr('title'), 'body' => $this->paragraphs($page->tr('body'))] : null,
            'services' => Service::visible()->get()->map(fn (Service $s) => ['id' => $s->id, 'slug' => $s->slug, 'name' => $s->tr('name')])->values(),
            'team' => TeamMember::visible()->get()->map(fn (TeamMember $m) => [
                'id' => $m->id, 'name' => $m->tr('name'), 'role' => $m->tr('role'),
                'photo' => $m->photo_path ? Storage::disk('public')->url($m->photo_path) : null,
            ])->values(),
        ]);
    }

    public function legal(string $slug): Response
    {
        abort_unless(in_array($slug, self::LEGAL, true), 404);
        // Draft text stays hidden until someone reviews and publishes it.
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return Inertia::render('legal', ['title' => $page->tr('title'), 'paragraphs' => $this->paragraphs($page->tr('body'))]);
    }

    /** Plain text only: blank line = new paragraph. Rendered as text by React, never as HTML. */
    private function paragraphs(mixed $body): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $body) ?: [])));
    }
}
