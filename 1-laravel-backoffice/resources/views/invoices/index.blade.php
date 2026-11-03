@extends('layouts.app')
@section('title', 'Faturalar')

@section('actions')
    <a href="{{ route('faturalar.export') }}" class="btn-secondary">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        Excel
    </a>
    <button type="button" class="btn-secondary" data-modal-url="{{ route('alislar.create') }}" data-modal-title="Yeni Alış Faturası" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
        </svg>
        Alış Faturası
    </button>
    <button type="button" class="btn-primary" data-modal-url="{{ route('faturalar.create') }}" data-modal-title="Yeni Satış Faturası" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Satış Faturası
    </button>
@endsection

@section('content')

    @php
        $chips = [];
        if (! empty(request('search'))) $chips[] = ['label' => 'Arama', 'value' => request('search'), 'url' => request()->fullUrlWithQuery(['search' => null, 'page' => null])];
    @endphp
    @include('partials.chips')

    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <input type="text" name="search" value="{{ $filters['search'] }}" class="input sm:max-w-xs"
               placeholder="Fatura no veya cari adı ara...">
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Ara</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tip</th>
                    <th>Cari</th>
                    <th>Tarih</th>
                    <th class="!text-right">Ara Toplam</th>
                    <th class="!text-right">KDV</th>
                    <th class="!text-right">Genel Toplam</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="hover:bg-slate-50">
                        <td>
                            <a href="{{ route('faturalar.show', $invoice) }}" data-modal-url="{{ route('faturalar.show', $invoice) }}" data-modal-title="Fatura {{ $invoice->invoice_no }}" data-modal-wide="1" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $invoice->invoice_no }}</a>
                        </td>
                        <td>
                            <span class="{{ $invoice->typeBadgeClass() }}">{{ $invoice->typeShortLabel() }}</span>
                        </td>
                        <td>{{ $invoice->contact->name }}</td>
                        <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                        <td class="text-right tabular-nums">{{ $fmt($invoice->subtotal) }}</td>
                        <td class="text-right tabular-nums">%{{ rtrim(rtrim(number_format($invoice->tax_rate, 2, ',', '.'), '0'), ',') }} ({{ $fmt($invoice->tax_amount) }})</td>
                        <td class="text-right font-bold">{{ $fmt($invoice->total) }}</td>
                        <td class="text-right tabular-nums">
                            <a href="{{ route('faturalar.show', $invoice) }}" data-modal-url="{{ route('faturalar.show', $invoice) }}" data-modal-title="Fatura {{ $invoice->invoice_no }}" data-modal-wide="1" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Görüntüle</a>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 8, 'icon' => 'invoice', 'title' => 'Henüz fatura yok', 'desc' => 'İlk faturanızı kesin — stoklar düşer, cari bakiyesi otomatik işlenir.', 'action' => 'Yeni Fatura Kes', 'actionUrl' => 'http://localhost/faturalar/create', 'actionTitle' => 'Yeni Satış Faturası', 'wide' => 1])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
