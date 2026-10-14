<?php

namespace App\Notifications;

use App\Models\FamilyPack;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyPackReminderNotification extends Notification
{
    public function __construct(private readonly FamilyPack $familyPack)
    {
    }

    public function via(object $notifiable): array
    {
        if (isset($notifiable->exists) && $notifiable->exists && !empty($notifiable->id)) {
            return ['mail', 'database'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $items = $this->familyPack->recommendations ?: [];
        $monthlyTotal = (float) ($this->familyPack->monthly_total ?: 0);
        $recipientName = (is_object($notifiable) && isset($notifiable->name) && !empty($notifiable->name))
            ? $notifiable->name
            : 'Prakruti Patron';

        $orderDate = $this->familyPack->created_at
            ? $this->familyPack->created_at->format('d M Y')
            : now()->subDays(27)->format('d M Y');

        $reminderDate = $this->familyPack->next_reminder_at
            ? $this->familyPack->next_reminder_at->format('d M Y')
            : now()->format('d M Y');

        $renewalDate = $this->familyPack->next_purchase_at
            ? $this->familyPack->next_purchase_at->format('d M Y')
            : now()->addDays(3)->format('d M Y');

        $reorderUrl = $this->familyPackUrl();

        $whatsappText = "Namaste Prakruti! I would like to refill my 30-Day Family Ration Basket (Est. Total: ₹"
            . number_format($monthlyTotal, 2)
            . "). Please confirm my previous monthly basket items.";
        $whatsappUrl = 'https://wa.me/919876543210?text=' . urlencode($whatsappText);

        return (new MailMessage)
            ->subject('🌾 Prakruti Monthly Ration Refill: Your Family Nutrition Basket is Ready to Reorder')
            ->view('emails.family_pack_reminder', [
                'recipientName' => $recipientName,
                'items'         => $items,
                'monthlyTotal'  => $monthlyTotal,
                'orderDate'     => $orderDate,
                'reminderDate'  => $reminderDate,
                'renewalDate'   => $renewalDate,
                'reorderUrl'    => $reorderUrl,
                'whatsappUrl'   => $whatsappUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Family Pack reorder reminder',
            'message' => 'Your saved 30-day monthly family ration basket is ready to reorder in 1-click.',
            'level' => 'info',
            'action_url' => $this->familyPackUrl(),
            'metadata' => [
                'family_pack_id' => $this->familyPack->id,
                'monthly_total' => (float) $this->familyPack->monthly_total,
                'next_purchase_at' => $this->familyPack->next_purchase_at?->toISOString(),
            ],
        ];
    }

    private function familyPackUrl(): string
    {
        $frontendUrl = env('FRONTEND_URL') ?: (app()->environment('local') ? 'http://127.0.0.1:5173' : config('app.url'));

        return rtrim((string) $frontendUrl, '/') . '/#family-pack';
    }
}
