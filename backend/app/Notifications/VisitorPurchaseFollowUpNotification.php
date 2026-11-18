<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitorPurchaseFollowUpNotification extends Notification
{
    public function __construct(private readonly string $visitorName)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) (config('app.frontend_url') ?: env('FRONTEND_URL') ?: config('app.url')), '/');

        return (new MailMessage)
            ->subject('A little note from Prakruti Heal')
            ->greeting('Namaste ' . ($this->visitorName ?: 'there') . ',')
            ->line('Thank you for visiting Prakruti Heal. We noticed you have not placed an order yet.')
            ->line('Explore our carefully selected natural and organic products whenever you are ready.')
            ->action('Visit Prakruti Heal', $frontendUrl)
            ->line('You received this message because you chose to receive updates from us.');
    }
}
