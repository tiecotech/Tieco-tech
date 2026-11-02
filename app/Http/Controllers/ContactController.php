<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ContactController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('contact', [
            'details' => SiteSetting::get('contact', []),
            'sent' => (bool) $request->session()->pull('contact_sent', false),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        // Honeypot: a real visitor never fills this hidden field. Drop the message quietly.
        if (filled($request->input('website'))) {
            return redirect()->route('site.contact');
        }

        $message = ContactMessage::create($request->safe()->except('website') + ['locale' => app()->getLocale()]);

        // Saved first; a notification problem must never lose the message or fail the visitor.
        try {
            $staff = User::where('is_active', true)->get()->filter(fn (User $u) => $u->hasPermission('messages.manage'));
            if ($staff->isNotEmpty()) {
                Notification::send($staff, new NewContactMessageNotification($message));
            }
        } catch (Throwable $e) {
            Log::warning('contact: notification failed', ['message_id' => $message->id, 'error' => $e->getMessage()]);
        }

        $request->session()->put('contact_sent', true);

        return redirect()->route('site.contact');
    }
}
