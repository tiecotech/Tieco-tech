<?php

namespace App\Http\Controllers;

use App\Support\Launch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/** The private preview link: lets its holder browse the real site before launch. */
class PreviewController extends Controller
{
    public function enter(string $token): RedirectResponse
    {
        $current = Launch::settings()['token'];
        abort_unless($current && hash_equals($current, $token), 404);

        return redirect('/')->withCookie(Cookie::make(Launch::COOKIE, $token, 60 * 24 * 60));
    }

    public function exit(): RedirectResponse
    {
        return redirect('/')->withCookie(Cookie::forget(Launch::COOKIE));
    }
}
