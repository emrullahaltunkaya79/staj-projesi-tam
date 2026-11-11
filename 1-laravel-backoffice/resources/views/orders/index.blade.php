@extends('layouts.app')
@section('title', 'E-ticaret Siparişleri')

@section('content')

    @php
        $durumAdlari = ['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim', 'cancelled' => 'İptal', 'return_requested' => 'İade Talebi', 'returned' => 'İade Edildi'];
        $odemeKisa = ['cspr' => 'CSPR', 'kapida' => 'Kapıda', 'havale' => 'Havale'];
        $odemeRenk = ['cspr' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300', 'kapida' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300', 'havale' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300'];
        $chips = [];
        if (! empty(request('status'))) $chips[] = ['label' => 'Durum', 'value' => $durumAdlari[request('status')] ?? request('status'), 'url' => request()->fullUrlWithQuery(['status' => null, 'page' => null])];
    @endphp
    @include('partials.chips')

    @php
        $counts = [
            'pending' => $orders->getCollection()->where('status', 'pending')->count(),
            'iade' => $orders->getCollection()->where('status', 'return_requested')->count(),
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
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">İade Talebi</div>
            <div class="mt-1 text-2xl font-bold {{ $counts['iade'] > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $counts['iade'] }}</div>
        </div>
    </div>

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <select name="status" class="input sm:w-48">
            <option value="">Tüm durumlar</option>
            @foreach (['pending' => 'Bekliyor', 'paid' => 'Ödendi', 'preparing' => 'Hazırlanıyor', 'shipped' => 'Kargolandı', 'delivered' => 'Teslim Edildi', 'return_requested' => 'İade Talebi', 'returned' => 'İade Edildi', 'cancelled' => 'İptal'] as $value => $label)
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
                    <th>Ödeme</th>
                    <th>Dış Referans</th>
                    <th class="!text-right">Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 {{ $order->status === 'return_requested' ? 'bg-amber-50/60 dark:bg-amber-950/20' : '' }}">
                        <td>
                            <a href="{{ route('siparisler.show', $order) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $order->order_no }}</a>
                            @if ($order->external_ref)
                                <div class="text-xs text-slate-400">{{ $order->external_ref }}</div>
                            @endif
                        </td>
                        <td>{{ $order->customer_name ?: ($order->contact->name ?? '—') }}</td>
                        <td><span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $odemeRenk[$order->payment_method] ?? $odemeRenk['cspr'] }}">{{ $odemeKisa[$order->payment_method] ?? 'CSPR' }}</span></td>
                        <td class="text-xs">{{ $order->external_ref ?: '—' }}</td>
                        <td class="text-right font-bold">{{ number_format((float) $order->total, 2, ',', '.') }} ₺</td>
                        <td>
                            <span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                            @if ($order->status === 'return_requested')
                                <div class="mt-0.5 text-[11px] font-semibold text-amber-600">{{ $order->iade_tutari ? number_format((float) $order->iade_tutari, 2, ',', '.').' ₺' : '' }}</div>
                            @endif
                        </td>
                        <td class="text-xs">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-right tabular-nums">
                            <div class="flex items-center justify-end gap-2">
                                @if (in_array($order->status, ['paid', 'preparing', 'shipped', 'delivered']))
                                    <a href="{{ route('siparisler.etiket', $order) }}" title="Kargo etiketini indir" data-turbo="false" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Etiket</a>
                                @endif
                                <a href="{{ route('siparisler.show', $order) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Detay</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('partials.empty', ['colspan' => 8, 'icon' => 'cart', 'title' => 'Henüz sipariş yok', 'desc' => "Siparişler e-ticaret API'sinden otomatik gelir (POST /api/v1/orders) — buradan ödeme, kargo ve iptal yönetilir."])
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
