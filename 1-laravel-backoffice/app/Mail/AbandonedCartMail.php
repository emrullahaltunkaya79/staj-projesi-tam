<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Terk edilmiş sepet hatırlatması — düz metin. */
class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ad,
        public array $ogeler,
        public string $baglanti,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sepetiniz sizi bekliyor 🛒',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.sepet-hatirlatma');
    }
}
