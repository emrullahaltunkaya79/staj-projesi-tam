{{--
    DOMPDF için fatura şablonu.
    DomPDF flexbox ve harici CSS dosyalarını desteklemediğinden
    tablo tabanlı düzen ve satır içi <style> kullanılır.
    Türkçe karakterler için DejaVu Sans, para birimi için güvenli "TL" kullanılır.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #334155; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .doc-title { font-size: 9px; letter-spacing: 3px; color: #4f46e5; font-weight: bold; }
        .invoice-no { font-size: 20px; font-weight: bold; color: #0f172a; }
        .header { border-bottom: 3px solid #0f172a; padding-bottom: 12px; }
        .buyer { background-color: #f1f5f9; border: 1px solid #e2e8f0; padding: 10px 14px; margin-top: 16px; }
        .buyer-label { font-size: 8px; font-weight: bold; color: #94a3b8; letter-spacing: 2px; }
        .items { margin-top: 16px; }
        .items th { border-bottom: 2px solid #0f172a; text-align: left; font-size: 9px; text-transform: uppercase; color: #475569; padding: 6px 4px; }
        .items td { border-bottom: 1px solid #e2e8f0; padding: 8px 4px; }
        .totals td { padding: 5px 8px; }
        .grand { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 12px; }
        .words { border: 1px solid #e2e8f0; padding: 8px 10px; color: #475569; }
        .sign { border-top: 1px dashed #94a3b8; padding-top: 6px; text-align: center; width: 40%; color: #64748b; font-size: 10px; }
        .footer { margin-top: 30px; text-align: center; color: #94a3b8; font-size: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px; }
    </style>
</head>
<body>

{{-- Üst bölüm: sol firma bilgileri / sağ fatura künyesi --}}
<table class="header">
        <tr>
            <td style="width: 60%;">
                @if ($invoice->company->logoUrl())
                    <img src="{{ $invoice->company->logoUrl() }}" style="height: 46px; margin-bottom: 6px;">
                @endif
                <div style="font-weight: bold; font-size: 15px; color: #0f172a;">{{ $invoice->company->name }}</div>
            <div class="muted" style="line-height: 1.6;">
                @if ($invoice->company->address) {{ $invoice->company->address }}<br> @endif
                @if ($invoice->company->phone) Tel: {{ $invoice->company->phone }}<br> @endif
                @if ($invoice->company->tax_number) Vergi No: {{ $invoice->company->tax_number }} @endif
            </div>
        </td>
        <td class="right" style="width: 40%;">
            <div class="doc-title">{{ strtoupper($invoice->typeLabel()) }}</div>
            <div class="invoice-no">{{ $invoice->invoice_no }}</div>
            <div class="muted">Tarih: {{ $invoice->issue_date->format('d.m.Y') }}</div>
            @if ($invoice->verification_code)
                @php
                    $qrDataUri = (new \chillerlan\QRCode\QRCode(
                        new \chillerlan\QRCode\QROptions(['scale' => 4, 'imageTransparent' => false])
                    ))->render($invoice->verificationUrl());
                @endphp
                <div style="margin-top: 6px; text-align: right;">
                    <img src="{{ $qrDataUri }}" style="width: 58px; height: 58px; border: 1px solid #e2e8f0; border-radius: 4px;">
                    <div style="font-size: 7px; color: #94a3b8; margin-top: 2px;">QR ile doğrula</div>
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- Alıcı (cari) bilgileri --}}
<div class="buyer">
    <div class="buyer-label">ALICI (CARİ)</div>
    <div style="margin-top: 4px;">
        <strong>{{ $invoice->contact->name }}</strong>
        @if ($invoice->contact->tax_number)
            <span class="muted"> — VKN: {{ $invoice->contact->tax_number }}</span>
        @endif
    </div>
    <div class="muted">
        @if ($invoice->contact->phone) {{ $invoice->contact->phone }} @endif
        @if ($invoice->contact->email) · {{ $invoice->contact->email }} @endif
        @if ($invoice->contact->address) <br>{{ $invoice->contact->address }} @endif
    </div>
</div>

{{-- Ürün kalemleri --}}
<table class="items">
    <thead>
    <tr>
        <th style="width: 6%;">#</th>
        <th>Ürün / Açıklama</th>
        <th class="right">Miktar</th>
        <th class="right">Birim Fiyat</th>
        <th class="right">Tutar</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($invoice->items as $i => $item)
        <tr>
            <td class="muted">{{ $i + 1 }}</td>
            <td><strong>{{ $item->product_name }}</strong></td>
            <td class="right">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
            <td class="right">{{ number_format($item->unit_price, 2, ',', '.') }} TL</td>
            <td class="right"><strong>{{ number_format($item->line_total, 2, ',', '.') }} TL</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- Toplamlar + yazı ile tutar --}}
<table style="margin-top: 16px;">
    <tr>
        <td style="width: 55%;">
            <div class="words"><strong>Yalnız:</strong> {{ $invoice->amountInWords() }}</div>
            @if ($invoice->notes)
                <div class="muted" style="margin-top: 6px;"><strong>Not:</strong> {{ $invoice->notes }}</div>
            @endif
        </td>
        <td style="width: 45%; padding-left: 20px;">
            <table class="totals">
                <tr>
                    <td>Ara Toplam</td>
                    <td class="right">{{ number_format($invoice->subtotal, 2, ',', '.') }} TL</td>
                </tr>
                <tr>
                    <td>KDV (%{{ rtrim(rtrim(number_format($invoice->tax_rate, 2, ',', '.'), '0'), ',') }})</td>
                    <td class="right">{{ number_format($invoice->tax_amount, 2, ',', '.') }} TL</td>
                </tr>
                <tr>
                    <td class="grand">Genel Toplam</td>
                    <td class="right grand">{{ number_format($invoice->total, 2, ',', '.') }} TL</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- İmza alanı --}}
<table style="margin-top: 55px;">
    <tr>
        <td class="sign">Düzenleyen / İmza</td>
        <td style="width: 20%;"></td>
        <td class="sign">Teslim Alan / İmza</td>
    </tr>
</table>

<div class="footer">
    Bu fatura {{ config('app.name') }} sistemi ile {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur.
</div>

</body>
</html>
