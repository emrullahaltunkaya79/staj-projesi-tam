<x-mail::message>
# Teklif: {{ $quote->quote_no }}

Merhaba {{ $quote->contact->name }},

**{{ $quote->company->name }}** tarafından hazırlanan teklifinizin ayrıntıları aşağıdadır; PDF kopyası ektedir.

<x-mail::table>
| | |
| --- | ---: |
| Teklif No | **{{ $quote->quote_no }}** |
| Tarih | {{ $quote->issue_date->format('d.m.Y') }} |
@if ($quote->valid_until)
| Geçerlilik | {{ $quote->valid_until->format('d.m.Y') }} |
@endif
| Ara Toplam | {{ $fmt($quote->subtotal) }} |
| KDV | {{ $fmt($quote->tax_amount) }} |
| **Genel Toplam** | **{{ $fmt($quote->total) }}** |
</x-mail::table>

@if ($quote->notes)
{{ $quote->notes }}
@endif

Teklifi onaylamak için bize ulaşabilirsiniz.

Teşekkürler,<br>
{{ $quote->company->name }}
</x-mail::message>
