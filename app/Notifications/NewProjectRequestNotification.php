<?php

namespace App\Notifications;

use App\Models\ProjectRequest;
use App\Support\Mailing;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to staff who can manage requests (in-dashboard always, email when a mail provider is configured) and to the owner's alert inbox. */
class NewProjectRequestNotification extends Notification
{
    public function __construct(public ProjectRequest $request) {}

    public function via(object $notifiable): array
    {
        return Mailing::staffChannels($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_request.new',
            'request_id' => $this->request->id,
            'number' => $this->request->number,
            'customer' => $this->request->customer_name,
            'project_type' => $this->request->project_type_snapshot['name']['en'] ?? null,
            'total_minor' => $this->request->total_minor,
            'currency' => $this->request->currency,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request;
        $type = $r->project_type_snapshot['name']['ar'] ?? $r->project_type_snapshot['name']['en'] ?? '-';

        return (new MailMessage)
            ->subject("طلب مشروع جديد / New project request {$r->number}")
            ->greeting('طلب جديد على الموقع — New request')
            ->line("العميل / Customer: {$r->customer_name}")
            ->line('الهاتف / Phone: '.($r->phone ?: '-'))
            ->line('الإيميل / Email: '.($r->email ?: '-'))
            ->line("نوع المشروع / Project: {$type}")
            ->line('التقدير / Estimate: '.number_format($r->total_minor / 100, 2).' '.$r->currency)
            ->action('افتح الطلب في لوحة التحكم / Open in dashboard', url('/admin/requests/'.$r->id));
    }
}
