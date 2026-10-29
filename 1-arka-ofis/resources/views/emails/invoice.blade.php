<x-mail::message>
# {{ $invoice->typeLabel() }}: {{ $invoice->invoice_no }}

Merhaba {{ $invoice->contact->name }},

**{{ $invoice->company->name }}** tarafından kesilen faturanızın ayrıntıları aşağıdadır; PDF kopyası ektedir.

<x-mail::table>
| | |
| --- | ---: |
| Fatura No | **{{ $invoice->invoice_no }}** |
| Tarih | {{ $invoice->issue_date->format('d.m.Y') }} |
@if ($invoice->due_date)
| Vade | {{ $invoice->due_date->format('d.m.Y') }} |
@endif
| Ara Toplam | {{ $fmt($invoice->subtotal) }} |
| KDV | {{ $fmt($invoice->tax_amount) }} |
| **Genel Toplam** | **{{ $fmt($invoice->total) }}** |
</x-mail::table>

Sorularınız için bize ulaşabilirsiniz.

Teşekkürler,<br>
{{ $invoice->company->name }}
</x-mail::message>
