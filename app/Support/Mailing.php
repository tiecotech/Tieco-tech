<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;
use Throwable;

class Mailing
{
    /** True only when a real mail transport is configured (not log/array). */
    public static function isConfigured(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    /** The owner's personal inbox for "something new arrived" alerts (Settings → Notifications). */
    public static function alertEmail(): ?string
    {
        $n = (array) SiteSetting::get('notifications', []);

        return ($n['enabled'] ?? true) && filled($n['email'] ?? null) ? (string) $n['email'] : null;
    }

    /**
     * Notify staff (dashboard + email) and also email the owner's alert inbox.
     * Each part is isolated: a mail failure never loses the in-dashboard notifications.
     *
     * @param  iterable<User>  $staff
     */
    public static function alertStaff(iterable $staff, Notification $notification): void
    {
        $staffEmails = [];
        foreach ($staff as $user) {
            $staffEmails[] = strtolower((string) $user->email);
            try {
                $user->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $alert = static::alertEmail();
        if ($alert && static::isConfigured() && ! in_array(strtolower($alert), $staffEmails, true)) {
            try {
                Notifier::route('mail', $alert)->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /** Channels for a staff alert: database for users, mail when a transport exists; anonymous routes get mail only. */
    public static function staffChannels(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return static::isConfigured() ? ['mail'] : [];
        }

        return static::isConfigured() ? ['database', 'mail'] : ['database'];
    }
}
