@extends('layouts.app')
@section('title', 'İade — '.$invoice->invoice_no)

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
        // Kalemler bazında kalan iade hakkı
        $refunded = [];
        foreach ($invoice->refunds as $refund) {
            foreach ($refund->items as $item) {
                $refunded[$item->product_id] = ($refunded[$item->product_id] ?? 0) + (float) $item->quantity;
            }
        }
    @endphp

    <form method="POST" action="{{ route('faturalar.iade', $invoice) }}" class="mx-auto max-w-3xl">
        @csrf

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">İade Faturası — {{ $invoice->invoice_no }}</h2>
            <p class="mb-6 text-sm text-slate-500">
                {{ $invoice->contact->name }} · {{ $invoice->issue_date->format('d.m.Y') }} · Toplam {{ $fmt($invoice->total) }}.
                İade edilecek miktarları girin; stoklar geri yüklenir ve cari bakiyesi <span class="font-semibold">iade tutarı</span> kadar düşer.
            </p>

            @if (count($refunded) > 0)
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Bu faturada daha önce iade yapılmış. Aşağıda <span class="font-semibold">kalan iade hakkı</span> gösterilmektedir.
                </div>
            @endif

            <table class="table-base">
                <thead>
                <tr>
                    <th>Ürün</th>
                    <th class="!text-right">Satılan</th>
                    <th class="!text-right">İade Edilen</th>
                    <th class="!text-right">Kalan Hakkı</th>
                    <th class="!text-right">Birim Fiyat</th>
                    <th style="width: 140px;">İade Miktarı</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($invoice->items as $item)
                    @php $remaining = (float) $item->quantity - ($refunded[$item->product_id] ?? 0); @endphp
                    <tr>
                        <td class="font-medium text-slate-700">{{ $item->product_name }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format($refunded[$item->product_id] ?? 0, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right font-semibold {{ $remaining > 0 ? 'text-slate-700' : 'text-slate-400' }}">{{ rtrim(rtrim(number_format($remaining, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right">{{ $fmt($item->unit_price) }}</td>
                        <td>
                            <input type="number" name="quantities[{{ $item->product_id }}]"
                                   value="{{ old('quantities.'.$item->product_id, $remaining) }}"
                                   min="0" max="{{ $remaining }}" step="any"
                                   {{ $remaining <= 0 ? 'disabled' : '' }} class="input">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @error('invoice') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-400">
                    İade Faturasını Kes
                </button>
                <a href="{{ route('faturalar.show', $invoice) }}" class="btn-secondary">Vazgeç</a>
                <span class="ml-auto text-xs text-slate-400">0 girdiğiniz kalemler iade edilmez.</span>
            </div>
        </div>
    </form>
@endsection
