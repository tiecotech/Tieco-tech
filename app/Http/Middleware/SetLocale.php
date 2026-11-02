<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/** Reads {locale} from the URL (en|ar), sets the app locale and shares locale props with Inertia. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');
        abort_unless(in_array($locale, config('tieco.locales'), true), 404);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->route()->forgetParameter('locale'); // controllers don't need the argument
        Inertia::share(Locale::props($request, $locale));

        return $next($request);
    }
}
