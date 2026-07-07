<?php

namespace App\Notifications\Billing;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Assinatura cancelada. */
class SubscriptionCanceledNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = config("plans.plans.{$notifiable->plan}.name") ?? ucfirst((string) $notifiable->plan);

        return (new MailMessage)
            ->subject('Assinatura cancelada — AcadFlow')
            ->markdown('emails.subscription-canceled', [
                'name'    => $notifiable->display_name ?: $notifiable->name,
                'plan'    => $plan,
                'expires' => optional($notifiable->plan_expires_at)->format('d/m/Y'),
                'url'     => rtrim((string) config('app.url'), '/').'/settings/plans',
            ]);
    }
}
