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

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['items', 'payments', 'shippingMethod']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Order :number received', ['number' => $this->order->order_number]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.confirmation',
            with: [
                'statusLabel' => $this->label($this->orderStatus()),
                'paymentStatusLabel' => $this->label($this->paymentStatus()),
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

    protected function orderStatus(): OrderStatus
    {
        $status = $this->order->getAttribute('status');

        return $status instanceof OrderStatus ? $status : OrderStatus::from((string) $status);
    }

    protected function paymentStatus(): PaymentStatus
    {
        $status = $this->order->getAttribute('payment_status');

        return $status instanceof PaymentStatus ? $status : PaymentStatus::from((string) $status);
    }

    protected function label(OrderStatus|PaymentStatus $status): string
    {
        return Str::headline($status->value);
    }
}
