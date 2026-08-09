<?php

namespace App\Support\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Orders\OrderNotificationManager;

class AdminOrderManager
{
    public function updateOrderStatus(Order $order, OrderStatus $status, User $user, ?string $note = null): Order
    {
        $fromStatus = $this->orderStatus($order);

        if ($fromStatus === $status && ! filled($note)) {
            return $order;
        }

        $order->forceFill([
            'status' => $status,
            'cancelled_at' => $status === OrderStatus::Cancelled ? now() : $order->cancelled_at,
        ])->save();

        $order->statusEvents()->create([
            'user_id' => $user->id,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'note' => $note,
        ]);

        $order = $order->refresh();

        if ($fromStatus !== $status) {
            app(OrderNotificationManager::class)->sendOrderStatusUpdated($order, $fromStatus, $status);
        }

        return $order;
    }

    public function updatePaymentStatus(Order $order, PaymentStatus $status, User $user, ?string $note = null): Order
    {
        $fromStatus = $this->paymentStatus($order);

        if ($fromStatus === $status && ! filled($note)) {
            return $order;
        }

        $order->forceFill([
            'payment_status' => $status,
        ])->save();

        $payment = $order->payments()->latest()->first();

        if (! $payment instanceof Payment) {
            $payment = $order->payments()->create([
                'method' => 'manual',
                'status' => $status,
                'amount' => $order->grand_total,
                'provider' => 'offline',
            ]);
        }

        $payment->forceFill([
            'status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? now() : $payment->paid_at,
        ])->save();

        $payment->events()->create([
            'user_id' => $user->id,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'note' => $note,
        ]);

        return $order->refresh();
    }

    public function addNote(Order $order, User $user, string $body, bool $isCustomerVisible = false): void
    {
        $order->notes()->create([
            'user_id' => $user->id,
            'is_customer_visible' => $isCustomerVisible,
            'body' => trim($body),
        ]);
    }

    protected function orderStatus(Order $order): OrderStatus
    {
        $status = $order->getAttribute('status');

        return $status instanceof OrderStatus ? $status : OrderStatus::from((string) $status);
    }

    protected function paymentStatus(Order $order): PaymentStatus
    {
        $status = $order->getAttribute('payment_status');

        return $status instanceof PaymentStatus ? $status : PaymentStatus::from((string) $status);
    }
}
