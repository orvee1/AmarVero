<?php

namespace App\Support\Orders;

use App\Enums\OrderStatus;
use App\Mail\Orders\OrderConfirmationMail;
use App\Mail\Orders\OrderStatusUpdatedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderNotificationManager
{
    public function sendOrderConfirmation(Order $order): void
    {
        $order = $this->mailReadyOrder($order);

        $this->queue($order, new OrderConfirmationMail($order));
    }

    public function sendOrderStatusUpdated(Order $order, OrderStatus $fromStatus, OrderStatus $toStatus): void
    {
        if ($fromStatus === $toStatus) {
            return;
        }

        $order = $this->mailReadyOrder($order);

        $this->queue($order, new OrderStatusUpdatedMail($order, $fromStatus, $toStatus));
    }

    protected function mailReadyOrder(Order $order): Order
    {
        return $order->loadMissing(['items', 'payments', 'shippingMethod']);
    }

    protected function queue(Order $order, OrderConfirmationMail|OrderStatusUpdatedMail $mailable): void
    {
        $email = trim((string) $order->email);

        if ($email === '') {
            return;
        }

        $name = trim((string) $order->customer_name);

        Mail::to($email, $name === '' ? null : $name)->queue($mailable);
    }
}
