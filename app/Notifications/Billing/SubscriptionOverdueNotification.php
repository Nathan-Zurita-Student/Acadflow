<?php

namespace App\Notifications\Billing;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Pagamento em atraso (aviso de expiração). */
class SubscriptionOverdueNotification extends Notification
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
            ->subject('Pagamento pendente — AcadFlow')
            ->markdown('emails.subscription-overdue', [
                'name' => $notifiable->display_name ?: $notifiable->name,
                'plan' => $plan,
                'url'  => rtrim((string) config('app.url'), '/').'/settings/plans',
            ]);
    }
}
