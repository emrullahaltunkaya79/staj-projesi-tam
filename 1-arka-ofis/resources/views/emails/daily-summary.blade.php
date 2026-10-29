<x-mail::message>
# Günlük Ön Muhasebe Özeti

**{{ $companyName }}** için bugünün özeti aşağıdadır.

@if (count($overdue) > 0)
## ⚠ Vadesi Geçen Faturalar

<x-mail::table>
| Fatura | Cari | Vade | Gecikme | Kalan |
| --- | --- | --- | --- | ---: |
@foreach ($overdue as $row)
| {{ $row['invoice_no'] }} | {{ $row['contact'] }} | {{ $row['due_date'] }} | {{ $row['days'] }} gün | {{ number_format($row['remaining'], 2, ',', '.') }} ₺ |
@endforeach
</x-mail::table>
@endif

@if (count($upcoming) > 0)
## 📅 7 Gün İçinde Vadesi Gelecekler

<x-mail::table>
| Fatura | Cari | Vade | Kalan |
| --- | --- | --- | ---: |
@foreach ($upcoming as $row)
| {{ $row['invoice_no'] }} | {{ $row['contact'] }} | {{ $row['due_date'] }} | {{ number_format($row['remaining'], 2, ',', '.') }} ₺ |
@endforeach
</x-mail::table>
@endif

@if (count($lowStock) > 0)
## 📦 Kritik Stok

<x-mail::table>
| Ürün | Kod | Stok |
| --- | --- | ---: |
@foreach ($lowStock as $row)
| {{ $row['name'] }} | {{ $row['code'] ?: '—' }} | {{ $row['stock'] }} |
@endforeach
</x-mail::table>
@endif

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
