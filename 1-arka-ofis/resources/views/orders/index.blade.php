@extends('layouts.app')
@section('title', 'E-ticaret Siparişleri')

@section('content')

    @php
        $durumAdlari = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim', 'cancelled' => 'İptal'];
        $chips = [];
        if (! empty(request('status'))) $chips[] = ['label' => 'Durum', 'value' => $durumAdlari[request('status')] ?? request('status'), 'url' => request()->fullUrlWithQuery(['status' => null, 'page' => null])];
    @endphp
    @include('partials.chips')

    @php
        $counts = [
            'pending' => $orders->getCollection()->where('status', 'pending')->count(),
            'paid' => $orders->getCollection()->where('status', 'paid')->count(),
            'shipping' => $orders->getCollection()->whereIn('status', ['preparing', 'shipped'])->count(),
            'revenue' => $orders->getCollection()->whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])->sum('total'),
        ];
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Bekleyen</div>
            <div class="mt-1 text-2xl font-bold text-amber-600">{{ $counts['pending'] }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Ödenen</div>
            <div class="mt-1 text-2xl font-bold text-emerald-600">{{ $counts['paid'] }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kargoda</div>
            <div class="mt-1 text-2xl font-bold text-indigo-600">{{ $counts['shipping'] }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Bu Sayfa Ciro</div>
            <div class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($counts['revenue'], 2, ',', '.') }} ₺</div>
        </div>
    </div>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="status" class="input sm:w-48">
            <option value="">Tüm durumlar</option>
            @foreach (['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargolandı', 'delivered' => 'Teslim Edildi', 'cancelled' => 'İptal'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-secondary !h-10 sm:w-28">Filtrele</button>
    </form>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Sipariş No</th>
                    <th>Müşteri</th>
                    <th>Dış Referans</th>
                    <th class="!text-right">Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td>
                            <a href="{{ route('siparisler.show', $order) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $order->order_no }}</a>
                            @if ($order->external_ref)
                                <div class="text-xs text-slate-400">{{ $order->external_ref }}</div>
                            @endif
                        </td>
                        <td>{{ $order->customer_name ?: ($order->contact->name ?? '—') }}</td>
                        <td class="text-xs">{{ $order->external_ref ?: '—' }}</td>
                        <td class="text-right font-bold">{{ number_format((float) $order->total, 2, ',', '.') }} ₺</td>
                        <td><span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span></td>
                        <td class="text-xs">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-right tabular-nums">
                            <div class="flex items-center justify-end gap-2">
                                @if (in_array($order->status, ['paid', 'preparing', 'shipped', 'delivered']))
                                    <a href="{{ route('siparisler.etiket', $order) }}" title="Kargo etiketini indir" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Etiket</a>
                                @endif
                                <a href="{{ route('siparisler.show', $order) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Detay</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 7, 'icon' => 'cart', 'title' => 'Henüz sipariş yok', 'desc' => "Siparişler e-ticaret API'sinden otomatik gelir (POST /api/v1/orders) — buradan ödeme, kargo ve iptal yönetilir."])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
