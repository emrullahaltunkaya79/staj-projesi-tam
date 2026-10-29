@extends('layouts.app')
@section('title', 'Teklif '.$quote->quote_no)

@section('actions')
    <a href="{{ route('teklifler.index') }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Geri Dön
    </a>
    @if ($quote->status === 'acik')
        <a href="{{ route('teklifler.edit', $quote) }}" class="btn-secondary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Düzenle
        </a>
    @endif
    @if ($quote->status === 'acik')
        <form method="POST" action="{{ route('teklifler.durum', $quote) }}">
            @csrf
            <input type="hidden" name="status" value="onaylandi">
            <button type="submit" class="btn bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Onayla
            </button>
        </form>
        <form method="POST" action="{{ route('teklifler.durum', $quote) }}"
              onsubmit="return confirm('Teklif reddedilsin mi?');">
            @csrf
            <input type="hidden" name="status" value="reddedildi">
            <button type="submit" class="btn-danger">Reddet</button>
        </form>
    @endif
    @if ($quote->status === 'onaylandi')
        <form method="POST" action="{{ route('teklifler.faturalandir', $quote) }}"
              onsubmit="return confirm('Teklif satış faturasına dönüştürülsün mü? Stok kontrolü yapılır ve yetersizse işlem iptal edilir.');">
            @csrf
            <button type="submit" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                Faturalaştır
            </button>
        </form>
    @endif
    <a href="{{ route('teklifler.pdf', $quote) }}" class="btn-secondary" title="Teklifi PDF olarak indir">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        PDF İndir
    </a>
    <form method="POST" action="{{ route('teklifler.email', $quote) }}"
          onsubmit="return confirm('Teklif PDF olarak carinin e-posta adresine gönderilsin mi?');">
        @csrf
        <button type="submit" class="btn-secondary" title="Cariye e-posta ile gönder">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            E-posta ile Gönder
        </button>
    </form>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    <div class="mx-auto max-w-4xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
        {{-- Üst bölüm --}}
        <div class="flex flex-col gap-6 border-b-2 border-slate-800 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                @if ($quote->company->logoUrl())
                    <img src="{{ $quote->company->logoUrl() }}" alt="{{ $quote->company->name }}" class="h-14 w-auto rounded-lg border border-slate-200 bg-white p-1">
                @else
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-sm font-black text-white">ÖM</div>
                @endif
                <div>
                    <div class="text-lg font-bold text-slate-900">{{ $quote->company->name }}</div>
                    <div class="mt-1 text-xs leading-5 text-slate-500">
                        @if ($quote->company->address) {{ $quote->company->address }}<br> @endif
                        @if ($quote->company->phone) Tel: {{ $quote->company->phone }} @endif
                    </div>
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">Teklif</div>
                <div class="mt-1 text-2xl font-black tracking-wide text-slate-900">{{ $quote->quote_no }}</div>
                <div class="mt-1 text-sm text-slate-500">
                    Tarih: {{ $quote->issue_date->format('d.m.Y') }}
                    @if ($quote->valid_until)
                        · Geçerlilik: {{ $quote->valid_until->format('d.m.Y') }}
                    @endif
                </div>
                <div class="mt-2"><span class="{{ $quote->statusBadgeClass() }}">{{ $quote->statusLabel() }}</span></div>
            </div>
        </div>

        {{-- Cari --}}
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-5 py-4">
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Müşteri (Cari)</div>
            <div class="mt-1 text-sm">
                <span class="font-bold text-slate-800">{{ $quote->contact->name }}</span>
                @if ($quote->contact->phone)
                    <span class="text-slate-500"> · {{ $quote->contact->phone }}</span>
                @endif
            </div>
        </div>

        {{-- Kalemler --}}
        <table class="mt-6 w-full text-left text-sm">
            <thead>
            <tr class="border-b-2 border-slate-800 text-[11px] font-bold uppercase tracking-wide text-slate-600">
                <th class="py-2.5 pr-2 w-8">#</th>
                <th class="py-2.5 pr-2">Ürün / Açıklama</th>
                <th class="py-2.5 pr-2 text-right">Miktar</th>
                <th class="py-2.5 pr-2 text-right">Birim Fiyat</th>
                <th class="py-2.5 text-right">Tutar</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($quote->items as $i => $item)
                <tr class="border-b border-slate-100">
                    <td class="py-3 pr-2 text-slate-400">{{ $i + 1 }}</td>
                    <td class="py-3 pr-2 font-medium text-slate-800">{{ $item->product_name }}</td>
                    <td class="py-3 pr-2 text-right">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="py-3 pr-2 text-right">{{ $fmt($item->unit_price) }}</td>
                    <td class="py-3 text-right font-semibold">{{ $fmt($item->line_total) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{-- Toplamlar --}}
        <div class="mt-6 flex justify-end">
            <div class="w-full max-w-xs space-y-1.5 text-sm sm:w-64">
                <div class="flex justify-between text-slate-600">
                    <span>Ara Toplam</span>
                    <span>{{ $fmt($quote->subtotal) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>KDV (%{{ rtrim(rtrim(number_format($quote->tax_rate, 2, ',', '.'), '0'), ',') }})</span>
                    <span>{{ $fmt($quote->tax_amount) }}</span>
                </div>
                <div class="flex justify-between rounded-lg bg-slate-900 px-3 py-2 text-base font-bold text-white">
                    <span>Genel Toplam</span>
                    <span>{{ $fmt($quote->total) }}</span>
                </div>
            </div>
        </div>

        @if ($quote->notes)
            <div class="mt-6 text-xs text-slate-500"><span class="font-semibold">Not:</span> {{ $quote->notes }}</div>
        @endif

        @if ($quote->convertedInvoice)
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Bu teklif faturalandırılmıştır:
                <a href="{{ route('faturalar.show', $quote->convertedInvoice) }}" class="font-semibold underline">{{ $quote->convertedInvoice->invoice_no }}</a>
            </div>
        @endif
    </div>

    <p class="mx-auto mt-4 max-w-4xl text-center text-xs text-slate-400">
        Teklif stok ve bakiye etkisi yaratmaz; "Faturalaştır" ile satış faturasına dönüştürüldüğünde işlenir.
    </p>
@endsection
