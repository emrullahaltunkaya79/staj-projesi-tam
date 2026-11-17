<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * PDF ikili verisi kuyruk payload'ına JSON olarak yazıldığından
     * ham binary "Malformed UTF-8" hatasıyla kuyruğu kırar — base64 taşınır.
     */
    public string $pdfContents;

    public function __construct(
        public Quote $quote,
        string $pdfBinary,
        public string $pdfFilename,
    ) {
        $this->pdfContents = base64_encode($pdfBinary);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Teklifiniz: {$this->quote->quote_no} — {$this->quote->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote',
            with: [
                'fmt' => fn ($value) => number_format((float) $value, 2, ',', '.').' ₺',
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => base64_decode($this->pdfContents), $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
