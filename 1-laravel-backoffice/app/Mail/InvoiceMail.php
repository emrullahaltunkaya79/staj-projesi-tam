<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfContents,
        public string $pdfFilename,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Faturanız: {$this->invoice->invoice_no} — {$this->invoice->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'fmt' => fn ($value) => number_format((float) $value, 2, ',', '.').' ₺',
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContents, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
