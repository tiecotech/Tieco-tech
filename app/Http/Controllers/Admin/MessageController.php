<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $messages = ContactMessage::query()
            ->when(in_array($status, self::STATUSES, true), fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")->orWhere('subject', 'like', "%{$q}%")))
            ->latest()->paginate(20)->withQueryString()
            ->through(fn (ContactMessage $m) => [
                'id' => $m->id, 'name' => $m->name, 'subject' => $m->subject, 'contact' => $m->email ?: $m->phone,
                'status' => $m->status, 'date' => $m->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('admin/messages/index', [
            'messages' => $messages,
            'filters' => ['q' => $q, 'status' => $status],
            'counts' => ContactMessage::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Request $request, ContactMessage $message): Response
    {
        // Opening a new message marks it read, but only for staff who may manage messages.
        if ($message->status === 'new' && $request->user()->hasPermission('messages.manage')) {
            $message->forceFill(['status' => 'read', 'read_at' => now()])->save();
        }

        return Inertia::render('admin/messages/show', ['message' => [
            'id' => $message->id, 'name' => $message->name, 'email' => $message->email, 'phone' => $message->phone,
            'subject' => $message->subject, 'body' => $message->message, 'preferred_contact' => $message->preferred_contact,
            'locale' => $message->locale, 'status' => $message->status, 'date' => $message->created_at?->format('Y-m-d H:i'),
        ]]);
    }

    public function status(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', self::STATUSES)]]);
        $old = $message->status;
        $message->forceFill(['status' => $data['status'], 'read_at' => $message->read_at ?? ($data['status'] !== 'new' ? now() : null)])->save();
        ActivityLog::record('message.status_changed', $message, ['old' => $old, 'new' => $data['status']]);

        return back();
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete(); // soft delete
        ActivityLog::record('message.deleted', $message);

        return redirect()->route('admin.messages.index');
    }
}
