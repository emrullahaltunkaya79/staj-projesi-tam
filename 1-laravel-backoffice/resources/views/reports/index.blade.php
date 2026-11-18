@extends('layouts.app')
@section('title', 'Raporlar')

@section('actions')
    {{-- ═══ Dışa aktarma (Alpine dropdown) + Dönem filtresi ═══ --}}
    <div x-data="raporFiltre" class="flex flex-wrap items-center gap-2">
        {{-- Dönem filtresi --}}
        <div class="relative">
            <button type="button" @click="donemAcik = !donemAcik" @click.outside="donemAcik = false"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 shadow-sm transition hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-600">
                <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="donemEtiketi">Son 12 Ay</span>
                <svg class="h-3 w-3 transition-transform" :class="donemAcik && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-cloak x-show="donemAcik" x-transition.opacity.duration.150ms
                 class="absolute right-0 top-11 z-30 w-52 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                <template x-for="s in hazirDonemler" :key="s.deger">
                    <button type="button" @click="donemSec(s.deger)"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-700"
                            :class="donem === s.deger ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300'">
                        <span x-text="s.etiket"></span>
                        <svg x-show="donem === s.deger" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </template>
            </div>
        </div>

        {{-- Dışa aktarma: kurumsal yeşil buton + açılır menü --}}
        <div class="relative" x-data="diswaAktarma" @click.outside="acik = false">
            <button type="button" @click="acik = !acik"
                    class="flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:bg-emerald-700 active:bg-emerald-800 active:scale-[.98]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Dışa Aktar
                <svg class="h-3.5 w-3.5 transition-transform" :class="acik && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-cloak x-show="acik" x-transition.opacity.duration.150ms
                 class="absolute right-0 top-full z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                <a href="{{ route('raporlar.excel') }}"
                   class="flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-emerald-50 hover:text-emerald-700 dark:text-slate-300 dark:hover:bg-slate-700/60">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m4 6V7m4 12v-3M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                    Excel Olarak İndir (.xlsx)
                </a>
                <button type="button" @click="pdfAl"
                        class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-left text-xs font-semibold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-slate-700/60">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    PDF Raporu Al
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = fn ($value) => number_format((float) $value, 2, ',', '.').' ₺';
    @endphp

    {{-- Özet kartları — ikonlu dairesel kapsayıcılar + kibar tipografi --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Toplam Satış</div>
                    <div data-rapor-deger="sales_total" class="mt-1.5 text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $fmt($stats['sales_total']) }}</div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">Satış faturaları (KDV dahil)</div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h4m0 0l-4-4m4 4l-4 4m5 10a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Toplam Alış</div>
                    <div data-rapor-deger="purchase_total" class="mt-1.5 text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-amber-600 dark:text-amber-400">{{ $fmt($stats['purchase_total']) }}</div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">Alış faturaları (KDV dahil)</div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2 12h10l2-7H7M9 19a1 1 0 102 0 1 1 0 00-2 0zm8 0a1 1 0 102 0 1 1 0 00-2 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Brüt Kâr</div>
                    <div data-rapor-deger="gross_profit" class="mt-1.5 text-2xl xl:text-3xl leading-none font-extrabold tabular-nums {{ $stats['gross_profit'] >= 0 ? 'text-slate-800 dark:text-slate-100' : 'text-red-600 dark:text-red-400' }}">{{ $fmt($stats['gross_profit']) }}</div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">Satış − Alış (KDV hariç)</div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="card flex h-full flex-col justify-between p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Toplam Tahsilat</div>
                    <div data-rapor-deger="collection_total" class="mt-1.5 text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-indigo-600 dark:text-indigo-400">{{ $fmt($stats['collection_total']) }}</div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">Nakit + havale toplamı</div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Kasa Durumu: mini kartlar (gider, nakit, banka) --}}
    <div class="mt-4">
        <div class="mb-2.5 text-xs font-bold uppercase tracking-wider text-slate-400">Kasa Durumu</div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="card flex items-center gap-3.5 p-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Giderler</div>
                    <div data-rapor-deger="expense_total" class="mt-0.5 text-xl font-extrabold tabular-nums text-rose-600 dark:text-rose-400">{{ $fmt($stats['expense_total']) }}</div>
                </div>
            </div>
            <div class="card flex items-center gap-3.5 p-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kasada (Nakit)</div>
                    <div class="mt-0.5 text-xl font-extrabold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $fmt($stats['cash_nakit']) }}</div>
                </div>
            </div>
            <div class="card flex items-center gap-3.5 p-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Bankada (Havale)</div>
                    <div class="mt-0.5 text-xl font-extrabold tabular-nums text-indigo-600 dark:text-indigo-400">{{ $fmt($stats['cash_banka']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafikler — boş durum katmanlı --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Son 12 Ay — Satış ve Alış Cirosu
                </span>
                <span class="flex items-center gap-4 text-[11px] font-semibold text-slate-400">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-indigo-500"></span>Satış</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Alış</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>Gider</span>
                </span>
            </div>
            <div class="relative p-5">
                <canvas id="monthly-chart" height="130"></canvas>
                <div x-show="bosVeri" class="absolute inset-0 flex flex-col items-center justify-center gap-2.5">
                    <svg class="h-12 w-12 text-slate-200 dark:text-slate-700" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="text-sm font-semibold text-slate-400">Henüz yeterli satış verisi bulunmuyor</span>
                    <span class="text-xs text-slate-300 dark:text-slate-600">Fatura kestikçe aylık ciro grafiği burada oluşur</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                    <svg class="h-4 w-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.513A9.025 9.025 0 0120.488 9z"/></svg>
                    En Çok Satılan Ürünler (adet)
                </span>
            </div>
            <div class="relative p-5">
                <canvas id="top-products-chart" height="200"></canvas>
                <div x-show="urunBos" class="absolute inset-0 flex flex-col items-center justify-center gap-2.5">
                    <span class="text-3xl">🏅</span>
                    <span class="text-sm font-semibold text-slate-400">Henüz satılan ürün yok</span>
                    <span class="text-xs text-slate-300 dark:text-slate-600">İlk satıştan sonra dağılım burada görünür</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Alacak yaşlandırma — modern kart --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg shadow-slate-200/50 dark:border-slate-700 dark:bg-slate-800 dark:shadow-black/20">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-slate-700">
            <span class="flex items-center gap-2.5 text-sm font-bold text-slate-700 dark:text-slate-200">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                Alacak Yaşlandırma <span class="font-normal text-slate-400">(kalan tutarlar)</span>
            </span>
            <span class="text-xs font-medium text-slate-400">Vadesi geçen faturalar gecikme gününe göre gruplanır</span>
        </div>
        <div class="overflow-x-auto p-2">
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="px-4 py-2.5">Vade Durumu</th>
                    <th class="px-4 py-2.5 text-right">Fatura Sayısı</th>
                    <th class="px-4 py-2.5 text-right">Kalan Tutar</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($aging as $i => $bucket)
                    @php $riskli = $i >= 1 && $bucket['amount'] > 0; @endphp
                    <tr class="border-t border-slate-100 transition hover:bg-slate-50/80 dark:border-slate-700/60 dark:hover:bg-slate-700/30 {{ $riskli ? 'bg-rose-50/40 dark:bg-rose-950/10' : '' }}">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2 font-medium {{ $riskli ? 'text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-200' }}">
                                <span class="h-2 w-2 rounded-full {{ $bucket['count'] > 0 && $riskli ? 'bg-rose-500' : 'bg-emerald-400' }}"></span>
                                {{ $bucket['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-600 dark:text-slate-300">{{ $bucket['count'] }}</td>
                        <td class="px-4 py-3 text-right font-bold tabular-nums {{ $bucket['amount'] > 0 && $riskli ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }}">{{ $fmt($bucket['amount']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Not: Brüt kâr, satışların ve alışların KDV hariç (net) toplamları arasındaki farktır; stok maliyeti ağırlığı (FIFO) hesaba katılmaz.
    </p>

    {{-- Ürün kâr marjları --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header">Ürün Bazında Kâr Marjı</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Alış</th>
                        <th class="!text-right">Satış</th>
                        <th class="!text-right">Birim Kâr</th>
                        <th class="!text-right">Marj %</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($profitMargins as $row)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $row->name }}</td>
                            <td class="text-right text-slate-500">{{ $fmt($row->purchase_price ?? 0) }}</td>
                            <td class="text-right text-slate-500">{{ $fmt($row->sale_price) }}</td>
                            <td class="text-right font-bold {{ $row->margin > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $fmt($row->margin) }}</td>
                            <td class="text-right {{ ($row->pct ?? 0) > 0 ? 'text-emerald-500' : 'text-rose-500' }}">{{ $row->pct ?? '—' }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">Ürün yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">En Çok Kâr Getiren Ürünler (satılan)</div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="!text-right">Satılan</th>
                        <th class="!text-right">Toplam Kâr</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($profitByProduct as $row)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $row->product_name }}</td>
                            <td class="text-right">{{ rtrim(rtrim(number_format($row->qty, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="text-right font-bold text-emerald-600">{{ $fmt($row->total_profit) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-slate-400">Henüz satış yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Grafik verisi ve Chart.js --}}
    <script>
        window.reportData = {
            chart: @json($chart),
            topProducts: @json($topProducts),
        };
    </script>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                // Chart.js Turbo gezinmelerinde yeniden yüklenmez — eksikse burada yüklenir
                const chartYukle = () => new Promise((resolve) => {
                    if (typeof Chart !== 'undefined') { resolve(true); return; }
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js';
                    s.onload = () => resolve(true);
                    s.onerror = () => resolve(false);
                    document.head.appendChild(s);
                });

                const ciz = () => {
                    if (!document.getElementById('monthly-chart')) return;
                    if (typeof Chart === 'undefined' || !window.reportData) return;

                    // Dönem değişimlerinde yeniden çizim: eski instance'ı yok et
                    ['monthly-chart', 'top-products-chart'].forEach((kimlik) => {
                        window.Chart.getChart(document.getElementById(kimlik))?.destroy();
                    });

                    const { chart, topProducts } = window.reportData;
                    const money = (v) => new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 0 }).format(v) + ' ₺';

                    new Chart(document.getElementById('monthly-chart'), {
                        type: 'bar',
                        data: {
                            labels: chart.labels,
                            datasets: [
                                {
                                    label: 'Satış',
                                    data: chart.sales,
                                    backgroundColor: '#4f46e5',
                                    borderRadius: 6,
                                },
                                {
                                    label: 'Alış',
                                    data: chart.purchases,
                                    backgroundColor: '#f59e0b',
                                    borderRadius: 6,
                                },
                                {
                                    label: 'Gider',
                                    data: chart.expenses,
                                    backgroundColor: '#ef4444',
                                    borderRadius: 6,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom' } },
                            scales: { y: { beginAtZero: true, ticks: { callback: money } } },
                        },
                    });

                    if (topProducts.labels.length > 0) {
                        new Chart(document.getElementById('top-products-chart'), {
                            type: 'doughnut',
                            data: {
                                labels: topProducts.labels,
                                datasets: [{
                                    data: topProducts.quantities,
                                    backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#64748b'],
                                }],
                            },
                            options: {
                                responsive: true,
                                plugins: { legend: { position: 'bottom' } },
                            },
                        });
                    }
                };

                // turbo:load dinleyicisi tek seferlik: betik her gezinmede yeniden
                // çalışır, koruma olmadan dinleyici birikir
                if (!window.__raporGrafikBagli) {
                    window.__raporGrafikBagli = true;
                    document.addEventListener('turbo:load', () => chartYukle().then(ciz));
                    // raporFiltre dönem seçiminde: veriler ucundan tazelenen reportData ile yeniden çiz
                    // (olay window'a dispatch ediliyor — dinleyici de window'da olmalı)
                    window.addEventListener('rapor-veriler', () => chartYukle().then(ciz));
                }
                ciz();
            })();
        </script>
    @endpush
@endsection
