<?php

namespace App\Notifications;

use App\Models\ProjectRequest;
use App\Support\Mailing;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to staff who can manage requests: in-dashboard always, email when a mail provider is configured. */
class NewProjectRequestNotification extends Notification
{
    public function __construct(public ProjectRequest $request) {}

    public function via(object $notifiable): array
    {
        return Mailing::isConfigured() ? ['database', 'mail'] : ['database'];
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
        return (new MailMessage)
            ->subject("New project request {$this->request->number}")
            ->line("{$this->request->customer_name} sent a new project request.")
            ->line('Estimated total: '.number_format($this->request->total_minor / 100, 2).' '.$this->request->currency);
    }
}
