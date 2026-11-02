<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RootRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->getPreferredLanguage(config('tieco.locales')) ?? config('tieco.default_locale');

        return redirect('/'.$locale);
    }
}
