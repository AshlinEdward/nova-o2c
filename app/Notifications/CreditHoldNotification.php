<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CreditHoldNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public string $customerName,
        public float $amount,
        public string $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'customer_name' => $this->customerName,
            'amount' => $this->amount,
            'message' => $this->message,
        ];
    }
}