<?php

namespace App\Mail\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class OrderStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public OrderStatus $fromStatus,
        public OrderStatus $toStatus,
    ) {
        $this->order->loadMissing(['items', 'payments', 'shippingMethod']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Order :number is now :status', [
                'number' => $this->order->order_number,
                'status' => Str::headline($this->toStatus->value),
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.status-updated',
            with: [
                'previousStatusLabel' => Str::headline($this->fromStatus->value),
                'statusLabel' => Str::headline($this->toStatus->value),
                'paymentStatusLabel' => Str::headline($this->paymentStatus()->value),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    protected function paymentStatus(): PaymentStatus
    {
        $status = $this->order->getAttribute('payment_status');

        return $status instanceof PaymentStatus ? $status : PaymentStatus::from((string) $status);
    }
}
