<?php

namespace App\Notifications;

use App\Models\ProjectRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmation to the customer. Only sent when a real mail provider is configured. */
class ProjectRequestReceivedNotification extends Notification
{
    public function __construct(public ProjectRequest $request, public string $locale) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ar = $this->locale === 'ar';

        return (new MailMessage)
            ->subject(($ar ? 'استلمنا طلبك ' : 'We received your request ').$this->request->number)
            ->line($ar ? 'شكرًا لك. حفظنا طلبك وسيراجعه فريقنا قريبًا.' : 'Thank you. We saved your request and our team will review it shortly.')
            ->line(($ar ? 'رقم الطلب: ' : 'Request number: ').$this->request->number)
            ->line($ar ? 'السعر الظاهر تقدير وليس عرضًا ملزمًا.' : 'The price shown is an estimate, not a binding offer.');
    }
}
