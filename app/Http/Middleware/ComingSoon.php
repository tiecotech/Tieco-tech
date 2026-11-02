<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Support\Launch;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/** Serves the coming-soon page on public routes until the site is launched (see App\Support\Launch). */
class ComingSoon
{
    public function handle(Request $request, Closure $next): Response
    {
        // Staff can look at the page itself with ?coming-soon, even after launch.
        $forced = $request->user() && $request->has('coming-soon');
        if (! $forced && (Launch::isLive() || Launch::canPreview($request))) {
            return $next($request);
        }

        $locale = (string) $request->route('locale');
        if (! in_array($locale, config('tieco.locales'), true)) {
            $locale = $request->getPreferredLanguage(config('tieco.locales')) ?? config('tieco.default_locale');
        }
        app()->setLocale($locale);
        $contact = (array) SiteSetting::get('contact', []);

        $response = Inertia::render('coming-soon', [
            'locale' => $locale,
            'text' => trans('soon'),
            'launchDate' => Launch::settings()['date'],
            'whatsapp' => $contact['whatsapp'] ?? null,
            'email' => $contact['email'] ?? null,
        ])->toResponse($request);

        // Never cache the gate, so visitors get the real site the moment it launches.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
