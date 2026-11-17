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

    /**
     * PDF ikili verisi kuyruk payload'ına JSON olarak yazıldığından
     * ham binary "Malformed UTF-8" hatasıyla kuyruğu kırar — base64 taşınır.
     */
    public string $pdfContents;

    public function __construct(
        public Invoice $invoice,
        string $pdfBinary,
        public string $pdfFilename,
    ) {
        $this->pdfContents = base64_encode($pdfBinary);
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
            Attachment::fromData(fn () => base64_decode($this->pdfContents), $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
