<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KritikStokMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Product $urun)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠ Kritik Stok Uyarısı: {$this->urun->name} — {$this->urun->stock_quantity} adet kaldı",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.kritik-stok',
            with: [
                'urun' => $this->urun,
                'onerilen' => $this->urun->onerilenSiparisAdedi(),
                'tedarikci' => $this->urun->supplier?->name,
            ],
        );
    }
}
