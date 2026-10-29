<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Siparişiniz alındı — {$this->order->order_no}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'items' => $this->order->items ?? [],
                'total' => number_format((float) $this->order->total, 2, ',', '.') . ' ₺',
            ],
        );
    }
}
