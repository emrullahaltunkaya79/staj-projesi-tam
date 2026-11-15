<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Doğum günü kuponu e-postası — düz metin. */
class BirthdayCouponMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ad,
        public string $kod,
        public string $sonGun,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎂 Doğum gününüz kutlu olsun — %15 hediye kuponunuz',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.dogum-gunu-kuponu');
    }
}
