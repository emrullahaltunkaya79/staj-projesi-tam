<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $companyName,
        public array $overdue,
        public array $upcoming,
        public array $lowStock,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Günlük Ön Muhasebe Özeti — {$this->companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.daily-summary',
        );
    }
}
