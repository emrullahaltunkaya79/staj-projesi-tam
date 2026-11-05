@extends('layouts.app')
@section('title', 'Dashboard')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('faturalar.create') }}" data-modal-title="Yeni Satış Faturası" data-modal-wide="1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Fatura
    </button>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
        $fmtShort = function ($value) {
            $value = (float) $value;
            if (abs($value) >= 1_000_000) return rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' M ₺';
            if (abs($value) >= 1_000) return rtrim(rtrim(number_format($value / 1_000, 1, ',', '.'), '0'), ',').' B ₺';
            return number_format($value, 0, ',', '.').' ₺';
        };
    @endphp

    {{-- Hızlı Erişim: en çok kullanılan sayfalar --}}
    <nav aria-label="Hızlı erişim" class="mb-4 grid grid-cols-3 gap-3 sm:grid-cols-6">
        @php
            $quickLinks = [
                ['Cariler', 'cariler.index', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-3-3'],
                ['Ürünler', 'urunler.index', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['Siparişler', 'siparisler.index', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['Faturalar', 'faturalar.index', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['Raporlar', 'raporlar.index', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['Etkinlikler', 'etkinlik.index', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012 2h2a2 2 0 012 2m-6 9l2 2 4-4'],
            ];
        @endphp
        @foreach ($quickLinks as [$label, $rota, $path])
            <a href="{{ route($rota) }}" aria-label="{{ $label }} sayfasına git"
               class="group flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3 text-center transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus-visible:outline-indigo-600">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-600 group-hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                    </svg>
                </span>
                <span class="text-[11px] font-bold text-slate-600 group-hover:text-slate-900">{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Vadesi geçen fatura uyarısı --}}
    @if ($overdue['count'] > 0)
        <a href="{{ route('faturalar.index') }}"
           class="mb-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-semibold text-red-800 transition hover:bg-red-100">
            <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $overdue['count'] }} faturanın vadesi geçti — tahsil edilemeyen toplam {{ $fmt($overdue['amount']) }}
            <span class="ml-auto text-xs font-normal text-red-500">Faturalara git →</span>
        </a>
    @endif

    {{-- 1) İstatistik kartları --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" role="list" aria-label="Özet istatistikler">
        {{-- Toplam Cari Sayısı --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Toplam Cari</div>
                    <div class="mt-1 text-3xl font-bold tabular-nums text-slate-800">{{ $stats['contact_count'] }}</div>
                    <div class="mt-1 text-xs text-slate-400">{{ $stats['customer_count'] }} müşteri · {{ $stats['supplier_count'] }} tedarikçi</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-3-3"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Stok / Ürün --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Stok & Ürün</div>
                    <div class="mt-1 text-3xl font-bold tabular-nums text-slate-800">{{ $stats['product_count'] }}</div>
                    <div class="mt-1 text-xs text-slate-400">Değer: {{ $fmtShort($stats['stock_value']) }} · Rezerve: {{ $stats['reserved_quantity'] }} adet</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Kasa durumu --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Kasa Durumu</div>
                    <div class="mt-1 text-3xl font-bold tabular-nums {{ $stats['cash_nakit'] + $stats['cash_banka'] >= 0 ? 'text-slate-800' : 'text-red-600' }}">{{ $fmtShort($stats['cash_nakit'] + $stats['cash_banka']) }}</div>
                    <div class="mt-1 text-xs text-slate-400">Nakit {{ $fmtShort($stats['cash_nakit']) }} · Banka {{ $fmtShort($stats['cash_banka']) }}</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Bu ay ciro --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Bu Ay Ciro</div>
                    <div class="mt-1 text-3xl font-bold tabular-nums text-slate-800">{{ $fmtShort($stats['month_sales']) }}</div>
                    <div class="mt-1 text-xs text-slate-400">Toplam {{ $fmtShort($stats['total_sales']) }} · {{ $stats['invoice_count'] }} fatura</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- 2) E-ticaret sipariş akışı + Bu ay nakit akışı --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- E-ticaret: sipariş özeti + son siparişler --}}
        <div class="card xl:col-span-2">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-violet-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    E-Ticaret Siparişleri
                </span>
                <a href="{{ route('siparisler.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Siparişlere git →</a>
            </div>

            <div class="grid grid-cols-3 divide-x divide-slate-100 dark:divide-slate-700">
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold {{ $stats['orders_pending'] > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $stats['orders_pending'] }}</div>
                    <div class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Ödeme Bekliyor</div>
                    @if ($stats['orders_pending'] > 0)
                        <div class="mt-0.5 text-xs text-slate-400">{{ $fmtShort($stats['orders_pending_amount']) }}</div>
                    @endif
                </div>
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold {{ $stats['orders_awaiting_shipment'] > 0 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $stats['orders_awaiting_shipment'] }}</div>
                    <div class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Kargolanacak</div>
                </div>
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold {{ $stats['orders_shipped'] > 0 ? 'text-emerald-600' : 'text-slate-400' }}">{{ $stats['orders_shipped'] }}</div>
                    <div class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Yolda</div>
                </div>
            </div>

            <div class="overflow-x-auto border-t border-slate-100 dark:border-slate-700">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Sipariş</th>
                        <th>Müşteri</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th class="!text-right">Tutar</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentOrders as $order)
                        <tr class="hover:bg-slate-50">
                            <td>
                                <a href="{{ route('siparisler.show', $order) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $order->order_no }}</a>
                            </td>
                            <td>{{ $order->customer_name ?: ($order->contact?->name ?? '—') }}</td>
                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                            <td><span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span></td>
                            <td class="text-right font-semibold tabular-nums">{{ $fmt($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">Henüz e-ticaret siparişi yok. API üzerinden sipariş oluşturulabilir.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bu ay nakit özeti: tek satır şerit --}}
        <div class="card mt-6 flex flex-wrap items-center gap-4 p-4">
            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Bu Ay</span>
            <span class="text-sm text-slate-600">Tahsilat <strong class="text-emerald-600">{{ $fmt($stats['month_collection']) }}</strong></span>
            <span class="text-sm text-slate-600">Gider <strong class="text-red-600">{{ $fmt($stats['month_expense']) }}</strong></span>
            <span class="text-sm text-slate-600">Net <strong class="text-slate-800">{{ ($stats['month_net'] >= 0 ? '+' : '') . $fmt($stats['month_net']) }}</strong></span>
            <span class="ml-auto flex items-center gap-2">
                <a href="{{ route('payments.create') }}" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat" class="btn-secondary !py-1.5 !px-3 text-xs">+ Tahsilat</a>
                <a href="{{ route('giderler.create') }}" data-modal-url="{{ route('giderler.create') }}" data-modal-title="Gider Kaydı" class="btn-secondary !py-1.5 !px-3 text-xs">+ Gider</a>
            </span>
        </div>

    </div>

    {{-- 3) Son Hareketler + Kritik Stok --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Kritik stok: ince şerit --}}
        <div class="card mt-6 flex flex-wrap items-center gap-3 p-4">
            <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-amber-700">
                ⚠ Kritik Stok
            </span>
            @forelse ($lowStockProducts as $product)
                <a href="{{ route('urunler.edit', $product) }}"
                   class="flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
                    {{ Str::limit($product->name, 22) }}
                    <span class="rounded px-1 font-bold {{ $product->stock_quantity == 0 ? 'bg-red-100 text-red-600' : 'bg-amber-100' }}">{{ $product->stock_quantity }}</span>
                </a>
            @empty
                <span class="text-xs text-slate-400">Kritik seviyede ürün yok 👍</span>
            @endforelse
            <a href="{{ route('urunler.index') }}" class="ml-auto text-xs font-semibold text-indigo-600 hover:text-indigo-700">Ürünler →</a>
        </div>
    </div>
@endsection
