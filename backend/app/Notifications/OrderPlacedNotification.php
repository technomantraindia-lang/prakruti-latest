<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Prakruti order ' . $this->order->order_num)
            ->greeting('New order received')
            ->line('Order ' . $this->order->order_num . ' has been placed by ' . ($this->order->user?->name ?? 'Guest customer') . '.')
            ->line('Order value: ₹' . number_format((float) $this->order->total, 2))
            ->action('Open order in admin', route('admin.orders.show', $this->order))
            ->line('This alert is also available in the admin notification bell.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New order received',
            'message' => 'Order ' . $this->order->order_num . ' for ₹' . number_format((float) $this->order->total, 2) . ' is pending.',
            'level' => 'success',
            'action_url' => route('admin.orders.show', $this->order),
            'metadata' => [
                'order_id' => $this->order->id,
                'order_num' => $this->order->order_num,
                'total' => (float) $this->order->total,
            ],
        ];
    }
}
