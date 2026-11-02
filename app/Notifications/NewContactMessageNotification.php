<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Support\Mailing;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageNotification extends Notification
{
    public function __construct(public ContactMessage $message) {}

    public function via(object $notifiable): array
    {
        return Mailing::isConfigured() ? ['database', 'mail'] : ['database'];
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
        return (new MailMessage)->subject('New contact message: '.$this->message->subject)
            ->line("{$this->message->name} wrote to you through the website.");
    }
}
