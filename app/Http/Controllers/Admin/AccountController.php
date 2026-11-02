<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function locale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:'.implode(',', config('tieco.locales'))]]);
        $request->user()->forceFill(['locale' => $data['locale']])->save();

        return back();
    }

    public function readNotifications(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
