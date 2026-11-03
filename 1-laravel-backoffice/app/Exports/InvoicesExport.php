<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoicesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function query()
    {
        return Invoice::query()->with('contact')->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['Fatura No', 'Tip', 'Cari', 'Tarih', 'Vade', 'Ara Toplam (₺)', 'KDV (₺)', 'Genel Toplam (₺)'];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_no,
            $invoice->isSale() ? 'Satış' : 'Alış',
            $invoice->contact->name,
            $invoice->issue_date->format('d.m.Y'),
            $invoice->due_date?->format('d.m.Y'),
            (float) $invoice->subtotal,
            (float) $invoice->tax_amount,
            (float) $invoice->total,
        ];
    }
}
