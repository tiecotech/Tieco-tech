<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Support\Mailing;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewContactMessageNotification extends Notification
{
    public function __construct(public ContactMessage $message) {}

    public function via(object $notifiable): array
    {
        return Mailing::staffChannels($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contact_message.new',
            'message_id' => $this->message->id,
            'from' => $this->message->name,
            'subject' => $this->message->subject,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->message;

        return (new MailMessage)->subject('رسالة جديدة / New contact message: '.$m->subject)
            ->greeting('رسالة جديدة من الموقع — New message')
            ->line("من / From: {$m->name}")
            ->line('الهاتف / Phone: '.($m->phone ?: '-').' · الإيميل / Email: '.($m->email ?: '-'))
            ->line(Str::limit((string) $m->message, 300))
            ->action('افتح الرسالة / Open in dashboard', url('/admin/messages/'.$m->id));
    }
}
