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
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
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

        {{-- Bu ay nakit akışı --}}
        <div class="card p-5">
            <div class="card-header border-b-0 p-0">Bu Ay Nakit Akışı</div>
            <p class="mt-1 text-xs text-slate-400">{{ now()->translatedFormat('F Y') }} — tahsilat ve gider hareketleri</p>

            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 dark:bg-emerald-950/30">
                    <span class="flex items-center gap-2 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-6-6m6 6l6-6"/></svg>
                        Tahsilat
                    </span>
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ $fmt($stats['month_collection']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-red-50 px-4 py-3 dark:bg-red-950/30">
                    <span class="flex items-center gap-2 text-sm font-medium text-red-800 dark:text-red-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 0l-6 6m6-6l6 6"/></svg>
                        Gider
                    </span>
                    <span class="font-bold text-red-700 dark:text-red-300">{{ $fmt($stats['month_expense']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border {{ $stats['month_net'] >= 0 ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/20' }} px-4 py-3">
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Net Akış</span>
                    <span class="font-bold {{ $stats['month_net'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">{{ ($stats['month_net'] >= 0 ? '+' : '') . $fmt($stats['month_net']) }}</span>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2 border-t border-slate-100 pt-3 dark:border-slate-700">
                <a href="{{ route('payments.create') }}" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat" class="btn-secondary !py-1.5 !px-3 text-xs">+ Tahsilat</a>
                <a href="{{ route('giderler.create') }}" data-modal-url="{{ route('giderler.create') }}" data-modal-title="Gider Kaydı" class="btn-secondary !py-1.5 !px-3 text-xs">+ Gider</a>
                <a href="{{ route('raporlar.index') }}" class="ml-auto text-xs font-semibold text-indigo-600 hover:text-indigo-700">Raporlar →</a>
            </div>
        </div>
    </div>

    {{-- 3) Son Hareketler + Kritik Stok --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Son Hareketler: son kesilen 5 fatura --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Son Hareketler
                </span>
                <span class="text-xs font-normal text-slate-400">Son kesilen 5 fatura</span>
                <a href="{{ route('faturalar.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Tümünü Gör →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Fatura No</th>
                        <th>Cari</th>
                        <th>Tarih</th>
                        <th>Tutar</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentInvoices as $invoice)
                        <tr class="hover:bg-slate-50">
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="{{ $invoice->typeBadgeClass() }}">{{ $invoice->typeShortLabel() }}</span>
                                    <a href="{{ route('faturalar.show', $invoice) }}" data-modal-url="{{ route('faturalar.show', $invoice) }}" data-modal-title="Fatura {{ $invoice->invoice_no }}" data-modal-wide="1" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $invoice->invoice_no }}</a>
                                </div>
                            </td>
                            <td>{{ $invoice->contact->name }}</td>
                            <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                            <td class="text-right font-semibold tabular-nums">{{ $fmt($invoice->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-400">Henüz fatura kesilmedi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kritik Stok: stoğu 5'in altına düşen ürünler --}}
        <div class="card border-amber-200">
            <div class="card-header flex items-center justify-between border-amber-200 bg-amber-50 text-amber-800">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Kritik Stok
                </span>
                <span class="text-xs font-normal text-amber-600">Stoğu 5'in altına düşen ürünler</span>
                <a href="{{ route('urunler.index', ['low_stock' => 1]) }}" class="text-xs font-semibold text-amber-700 hover:text-amber-800">Tümü →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th>Kod</th>
                        <th class="!text-right">Stok</th>
                        <th>Durum</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($lowStockProducts as $product)
                        <tr class="hover:bg-amber-50/50">
                            <td class="font-medium text-slate-700">{{ $product->name }}</td>
                            <td class="font-mono text-xs text-slate-500">{{ $product->code ?: '—' }}</td>
                            <td class="text-right">
                                <span class="{{ $product->stock_quantity == 0 ? 'badge-red' : 'badge-amber' }}">
                                    {{ $product->stock_quantity }} {{ $product->unit }}
                                </span>
                            </td>
                            <td class="text-xs font-semibold {{ $product->stock_quantity == 0 ? 'text-red-600' : 'text-amber-600' }}">
                                {{ $product->stock_quantity == 0 ? 'Stok tükendi — sipariş gerekli' : 'Yakında tükenecek — sipariş önerilir' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">
                                Kritik seviyede ürün yok. 👍
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
