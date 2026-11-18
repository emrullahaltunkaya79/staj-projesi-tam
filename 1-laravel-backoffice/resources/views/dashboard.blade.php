@extends('layouts.app')
@section('title', 'Yönetim Paneli')

@section('actions')
    {{-- ═══════════ Dönem Filtresi (Alpine) ═══════════ --}}
    <div x-data="donemFiltre" class="relative flex items-center gap-2">
        <button type="button" @click="acik = !acik"
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-600 shadow-sm transition hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-600">
            <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span x-text="donemEtiketi">Bu Ay</span>
            <svg class="h-3 w-3 transition-transform" :class="acik && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-cloak x-show="acik" @click.outside="acik = false" x-transition.opacity.duration.150ms
             class="absolute right-0 top-11 z-30 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-800">
            <template x-for="s in hazirDonemler" :key="s.deger">
                <button type="button" @click="donemSec(s.deger)"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-700"
                        :class="donem === s.deger ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300'">
                    <span x-text="s.etiket"></span>
                    <svg x-show="donem === s.deger" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
            </template>
            <div class="my-1 border-t border-slate-100 dark:border-slate-700"></div>
            <button type="button" @click="donem = 'ozel'; ozelAcik = true"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-700"
                    :class="donem === 'ozel' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300'">
                Özel Aralık
            </button>
            <div x-cloak x-show="ozelAcik" x-collapse class="mt-2 space-y-2 rounded-lg bg-slate-50 p-2.5 dark:bg-slate-900/50">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-400">Başlangıç</label>
                        <input type="date" x-model="ozelBas" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    </div>
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase text-slate-400">Bitiş</label>
                        <input type="date" x-model="ozelSon" class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    </div>
                </div>
                <button type="button" @click="ozelUygula" :disabled="!ozelBas || !ozelSon"
                        class="w-full rounded-lg bg-indigo-600 py-1.5 text-xs font-bold text-white transition hover:bg-indigo-700 disabled:opacity-40">Uygula</button>
            </div>
        </div>

        <button type="button" class="btn-primary" data-modal-url="{{ route('faturalar.create') }}" data-modal-title="Yeni Satış Faturası" data-modal-wide="1">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Fatura
        </button>
    </div>
@endsection

@section('content')
    <div x-data="panelVerisi" @panel-veriler.window="veri = $event.detail">
        {{-- Skeleton yükleme şeridi — dönem değişiminde veri çekilirken görünür --}}
        <div x-cloak x-show="yukleniyor" class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-hidden="true">
            @foreach (range(1, 4) as $i)
                <div class="card animate-pulse p-6">
                    <div class="h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="mt-3 h-8 w-32 rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="mt-3 h-3 w-40 rounded bg-slate-100 dark:bg-slate-800"></div>
                </div>
            @endforeach
        </div>

        {{-- ═══════════ 1) KPI KARTLARI — 4 eşit kolon ═══════════ --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" role="list" aria-label="Kritik göstergeler">

        {{-- 1.1 Toplam Ciro (finansal — yönetici) / Personel: açık iade talepleri — kartlar ilgili birime götürür --}}
        @can('finans')
        <a href="{{ route('raporlar.index') }}" title="Raporlar bölümüne git"
           class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md" :class="yukleniyor && 'animate-pulse opacity-40'">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Bu Dönem Ciro</div>
                    <div class="group relative mt-1.5 inline-flex cursor-help items-baseline gap-1.5">
                        <span class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="k.ciro.kisa.n"></span>
                        <span class="text-sm font-bold text-slate-400" x-text="k.ciro.kisa.ek"></span>
                        <span class="ipucu-baloncuk" x-text="k.ciro.tam"></span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-medium dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                            Fiziksel/B2B <strong class="tabular-nums text-slate-700 dark:text-slate-200" x-text="k.ciro.fiziksel_kisa"></strong>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                            E-Ticaret <strong class="tabular-nums text-violet-600 dark:text-violet-400" x-text="k.ciro.eticaret_kisa"></strong>
                        </span>
                    </div>
                    <div class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                         :class="k.ciro.degisim_tur === 'iyi' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : (k.ciro.degisim_tur === 'kotu' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300')"
                         x-text="k.ciro.degisim_metin"></div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13l2.5 2.5L9 12l3 3 4.5-5.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </a>
        @else
        <a href="{{ route('siparisler.index', ['status' => 'return_requested']) }}" title="İade taleplerine git"
           class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Açık İade Talepleri</div>
                    <div class="mt-1.5 text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="k.operasyonel.bekleyen_iade"></div>
                    <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">İade kararı yöneticide</div>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3M8 10l3-3M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2h-2.5"/>
                    </svg>
                </div>
            </div>
        </a>
        @endcan

            {{-- 1.2 E-Ticaret Siparişleri — bekleyen siparişlere götürür --}}
            <a href="{{ route('siparisler.index', ['status' => 'pending']) }}" title="Bekleyen e-ticaret siparişlerine git"
               class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md" :class="yukleniyor && 'animate-pulse opacity-40'">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">E-Ticaret Siparişleri</div>
                        <div class="mt-1.5 flex items-baseline gap-2">
                            <span class="relative inline-flex">
                                <span data-ozet="bekleyen" class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums" :class="k.siparis.bekleyen > 0 ? 'text-amber-600' : 'text-slate-800 dark:text-slate-100'" x-text="k.siparis.bekleyen"></span>
                                <span x-show="k.siparis.bekleyen > 0" class="absolute -right-3 top-0.5 flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                </span>
                            </span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">bekliyor · <span x-text="k.siparis.bekleyen_tutar_fmt"></span></span>
                        </div>
                        <template x-if="k.siparis.bekleyen === 0">
                            <div class="mt-2 flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <span>✓</span> Bekleyen sipariş yok — her şey güncel
                            </div>
                        </template>
                        <template x-if="k.siparis.bekleyen > 0">
                            <div class="mt-2 text-xs text-slate-500 font-medium dark:text-slate-400">
                                Onaylı: <strong class="text-indigo-600 dark:text-indigo-400" x-text="k.siparis.onayli"></strong>
                                · <span x-text="k.siparis.onayli_tutar_fmt"></span>
                            </div>
                        </template>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- 1.3 Kargo & Sevk — sipariş/sevk listesine götürür --}}
            <a href="{{ route('siparisler.index') }}" title="Kargo ve sevkiyat işlemlerine git"
               class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md" :class="yukleniyor && 'animate-pulse opacity-40'">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kargo & Sevk</div>
                        <div class="mt-1.5 flex items-baseline gap-2">
                            <span data-ozet="sevk_bugun" class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="k.kargo.bugun"></span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">bugün sevk</span>
                        </div>
                        <template x-if="k.kargo.bugun === 0">
                            <div class="mt-2 flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1.5 text-[11px] font-semibold text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                                <span>📦</span> Bugün henüz sevkiyat yapılmadı
                            </div>
                        </template>
                        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 font-medium dark:text-slate-400">
                            <span>Bu hafta: <strong data-ozet="sevk_hafta" class="text-slate-700 dark:text-slate-200" x-text="k.kargo.hafta"></strong></span>
                            <span>Yolda: <strong class="text-indigo-600 dark:text-indigo-400" x-text="k.kargo.yolda"></strong></span>
                        </div>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm10 3h4"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- 1.4 Net Kâr (finansal — yönetici) / Personel: kritik stok sayacı --}}
            @can('finans')
            <a href="{{ route('raporlar.index') }}" title="Raporlar bölümüne git"
               class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md" :class="yukleniyor && 'animate-pulse opacity-40'">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Net Kâr <span class="font-medium normal-case text-slate-400">(tahmini)</span></div>
                        <div class="group relative mt-1.5 inline-flex cursor-help items-baseline gap-1.5">
                            <span class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums" :class="k.net_kar.kisa.n.startsWith('-') ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400'" x-text="k.net_kar.kisa.n"></span>
                            <span class="text-sm font-bold text-slate-400" x-text="k.net_kar.kisa.ek"></span>
                            <span class="ipucu-baloncuk" x-text="k.net_kar.tam"></span>
                        </div>
                        <div class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                             :class="k.net_kar.marj_tur === 'iyi' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : (k.net_kar.marj_tur === 'uyari' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : (k.net_kar.marj_tur === 'kotu' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'))"
                             x-text="k.net_kar.marj === null ? 'Marj: —' : 'Marj %' + k.net_kar.marj"></div>
                        <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">
                            Maliyet −<span x-text="k.net_kar.maliyet_fmt"></span> · Kargo −<span x-text="k.net_kar.kargo_fmt"></span> · Komisyon −<span x-text="k.net_kar.komisyon_fmt"></span>
                        </div>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </a>
            @else
            <a href="{{ route('urunler.index', ['low_stock' => 1]) }}" title="Kritik stok ürünlerine git"
               class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kritik Stok</div>
                        <div class="mt-1.5 text-2xl xl:text-3xl leading-none font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="k.operasyonel.kritik_stok"></div>
                        <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">Eşik altındaki ürün sayısı</div>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </a>
            @endcan
        </div>

        {{-- ═══════════ 1b) FİNANSAL ALT BANT — vadesi geçen (1/3) + kasa (2/3) — yönetici ═══════════ --}}
        @can('finans')
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">

            {{-- Vadesi geçen alacak/borç (rose paleti) --}}
            <a href="{{ route('faturalar.index') }}" class="card flex h-full flex-col justify-between p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Vadesi Geçen Alacaklar</div>
                        <div class="group relative mt-1.5 inline-flex cursor-help items-baseline gap-1.5">
                            <span class="text-2xl xl:text-3xl leading-none font-extrabold tabular-nums" :class="k.alacak.adet > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-100'" x-text="k.alacak.kisa.n"></span>
                            <span class="text-sm font-bold text-slate-400" x-text="k.alacak.kisa.ek"></span>
                            <span class="ipucu-baloncuk" x-text="k.alacak.tam"></span>
                        </div>
                        <div class="mt-3 text-xs text-slate-500 font-medium dark:text-slate-400">
                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300" x-text="k.alacak.adet + ' fatura'"></span>
                            · Borç: <strong class="text-amber-600 dark:text-amber-400" x-text="k.alacak.borclar_fmt"></strong>
                        </div>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500 dark:bg-rose-950/50 dark:text-rose-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </a>

            {{-- Kasa & bu ay nakit akışı --}}
            <div class="card flex h-full flex-col justify-between p-6 xl:col-span-2 transition-opacity" :class="yukleniyor && 'animate-pulse opacity-40'">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Kasa Durumu</div>
                        <div class="mt-2 grid grid-cols-2 gap-4">
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nakit</div>
                                <div class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="k.kasa.nakit_kisa + ' ' + k.kasa.nakit_ek"></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Banka</div>
                                <div class="mt-0.5 text-lg font-extrabold tabular-nums text-slate-800 dark:text-slate-100" x-text="k.kasa.banka_kisa + ' ' + k.kasa.banka_ek"></div>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 font-medium dark:text-slate-400">
                            <span>Bu ay tahsilat: <strong class="text-emerald-600 dark:text-emerald-400" x-text="'+' + k.nakit_akisi.tahsilat_fmt"></strong></span>
                            <span>Gider: <strong class="text-rose-600 dark:text-rose-400" x-text="'−' + k.nakit_akisi.gider_fmt"></strong></span>
                            <span>Net: <strong class="text-slate-800 dark:text-slate-100" x-text="k.nakit_akisi.net_fmt"></strong></span>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2">
                        <a href="{{ route('virmanlar.index') }}" title="Kasa ↔ Banka aktarımı — virman kaydı oluştur"
                           class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">⇄ Virman</a>
                        <a href="{{ route('payments.create') }}" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat" class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">+ Tahsilat</a>
                        <a href="{{ route('giderler.create') }}" data-modal-url="{{ route('giderler.create') }}" data-modal-title="Gider Kaydı" class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">+ Gider</a>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        {{-- ═══════════ 2) GRAFİKLER ═══════════ --}}
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
            {{-- Satış trendi: fiziksel vs e-ticaret (30 gün) — finansal: yönetici --}}
            @can('finans')
            <div class="card xl:col-span-2">
                <div class="card-header flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                        <svg class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Satış Trendi — Son 30 Gün
                    </span>
                    <span class="flex items-center gap-4 text-[11px] font-semibold text-slate-400">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-400"></span>Fiziksel/B2B</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-violet-500"></span>E-Ticaret</span>
                    </span>
                </div>
                <div class="relative h-72 p-5">
                    <canvas id="grafikTrend" aria-label="Son 30 günlük satış trendi grafiği" role="img"></canvas>
                </div>
            </div>
            @endcan

            {{-- Kargo / gönderi durumu dağılımı --}}
            <div class="card @unless(auth()->user()->can('finans')) xl:col-span-3 @endunless">
                <div class="card-header flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                        <svg class="h-4 w-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/></svg>
                        Gönderi Durumu
                    </span>
                    <span class="text-[11px] font-semibold text-slate-400">aktif siparişler</span>
                </div>
                <div class="relative mx-auto h-72 p-5">
                    <canvas id="grafikDurum" aria-label="Gönderi durum dağılımı grafiği" role="img"></canvas>
                    <div x-show="toplamGonderi === 0" class="absolute inset-0 flex flex-col items-center justify-center gap-2 pt-8">
                        <svg class="h-10 w-10 text-slate-200 dark:text-slate-700" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/></svg>
                        <span class="text-xs font-semibold text-slate-400">Henüz gönderi akışı yok</span>
                        <span class="text-[11px] text-slate-300 dark:text-slate-600">İlk sipariş kargolandığında dağılım burada görünür</span>
                    </div>
                </div>
            </div>

            {{-- En çok satan ürünler --}}
            <div class="card xl:col-span-3">
                <div class="card-header flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                        <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h4m0 0l-4-4m4 4l-4 4m5 10a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        En Çok Satan Ürünler — Seçili Dönem (adet)
                    </span>
                    <a href="{{ route('urunler.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Ürünlere git →</a>
                </div>
                <div class="relative h-64 p-5">
                    <canvas id="grafikUrun" aria-label="En çok satan ürünler grafiği" role="img"></canvas>
                    <div x-show="urunGrafikBos" class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                        <span class="text-2xl">📈</span>
                        <span class="text-xs font-semibold text-slate-400">Seçili dönemde satış hareketi yok</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════ 3) OPERASYONEL AKIŞ ═══════════ --}}
        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
            {{-- Son e-ticaret siparişleri --}}
            <div class="card xl:col-span-2">
                <div class="card-header flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
                        <svg class="h-4 w-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Son E-Ticaret Siparişleri
                    </span>
                    <a href="{{ route('siparisler.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Tümü →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                        <tr>
                            <th>Sipariş</th>
                            <th>Müşteri</th>
                            <th>Kanal</th>
                            <th>Kargo</th>
                            <th>Durum</th>
                            <th class="!text-right">Tutar</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($sonSiparisler as $order)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40">
                                <td>
                                    <a href="{{ route('siparisler.show', $order) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ $order->order_no }}</a>
                                    <div class="text-[10px] text-slate-400">{{ $order->created_at->format('d.m H:i') }}</div>
                                </td>
                                <td class="max-w-40 truncate">{{ $order->customer_name ?: ($order->contact?->name ?? '—') }}</td>
                                <td><span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold {{ $order->kanalRozetClass() }}">{{ $order->kanalEtiketi() }}</span></td>
                                <td class="text-xs text-slate-500 dark:text-slate-400">{{ $order->carrier ?: ($order->status === 'pending' ? '—' : 'Bekliyor') }}</td>
                                <td><span class="{{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span></td>
                                <td class="text-right font-semibold tabular-nums">{{ number_format((float) $order->total, 2, ',', '.') }} ₺</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center">
                                    <span class="text-2xl">📦</span>
                                    <p class="mt-2 text-sm font-semibold text-slate-500 dark:text-slate-400">Henüz e-ticaret siparişi alınmadı</p>
                                    <p class="mt-0.5 text-xs text-slate-400">İlk sipariş API üzerinden geldiğinde burada listelenir</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Hızlı işlem menüsü + kritik stok --}}
            <div class="space-y-4">
                <div class="card p-4">
                    <div class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Hızlı İşlem</div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" data-modal-url="{{ route('faturalar.create') }}" data-modal-title="Yeni Satış Faturası" data-modal-wide="1"
                                class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-indigo-600">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Yeni Fatura Kes</span>
                        </button>
                        <a href="{{ route('siparisler.index') }}"
                           class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-sky-600">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H10a1 1 0 00-1 1v10M9 21h6M3 5h18v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/></svg>
                            </span>
                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Kargo / Sevk</span>
                        </a>
                        <a href="{{ route('urunler.index') }}"
                           class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-emerald-600">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </span>
                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Stok Güncelle</span>
                        </a>
                        <button type="button" data-modal-url="{{ route('payments.create') }}" data-modal-title="Yeni Tahsilat"
                                class="flex flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2 py-3.5 text-center transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-amber-600">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">Manuel Tahsilat</span>
                        </button>
                    </div>
                </div>

                <div class="card p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Kritik Stok
                        </span>
                        <a href="{{ route('urunler.index') }}" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700">Ürünler →</a>
                    </div>
                    <ul class="space-y-1.5">
                        @forelse ($kritikStok as $product)
                            <li class="rounded-lg border border-rose-100 bg-rose-50/60 px-3 py-2 dark:border-rose-900/40 dark:bg-rose-950/20">
                                <div class="flex items-center justify-between gap-2">
                                    <a href="{{ route('urunler.edit', $product) }}" class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700 hover:text-indigo-600 dark:text-slate-200">
                                        {{ Str::limit($product->name, 26) }}
                                    </a>
                                    <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300">
                                        {{ $product->stock_quantity }} / min {{ $product->min_stock_level }}
                                    </span>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between gap-2">
                                    <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">⚠ Kritik</span>
                                    @php $adet = $product->onerilenSiparisAdedi(); @endphp
                                    <a href="{{ route('alislar.create', array_filter([
                                            'tedarikci' => $product->supplier_id,
                                            'urun' => $product->id,
                                            'adet' => $adet > 0 ? $adet : null,
                                        ])) }}"
                                       class="inline-flex items-center gap-1 rounded-full bg-black px-2.5 py-1 text-[10px] font-bold text-white transition hover:bg-black/80 dark:bg-white dark:text-black dark:hover:bg-white/80">
                                        🛒 Tedarikçiye Sipariş Oluştur{{ $adet > 0 ? " ({$adet} adet)" : '' }}
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <span class="text-base">✓</span> Kritik seviyede ürün yok — stoklar sağlıklı
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Alpine bileşeni başlangıç verisi (sunucu render — ilk boyama dolu kartlarla) --}}
        <script>window.__panelBaslangic = @json($veri);</script>

        {{-- ═══════════ Chart.js ═══════════ --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
        <script>
        (function () {
            let trendGrafik, durumGrafik, urunGrafik;

            const temaKoyuMu = () => document.documentElement.classList.contains('dark');
            const renkler = () => temaKoyuMu()
                ? { kalem: '#cbd5e1', izgara: 'rgba(148,163,184,.15)' }
                : { kalem: '#475569', izgara: 'rgba(100,116,139,.15)' };
            const fmtKisa = (v) => {
                v = Math.abs(v);
                if (v >= 1_000_000) return (v / 1_000_000).toFixed(1).replace('.', ',') + 'M';
                if (v >= 1_000) return (v / 1_000).toFixed(1).replace('.', ',') + 'B';
                return String(Math.round(v));
            };

            function grafikleriCiz(g) {
                if (typeof Chart === 'undefined') { setTimeout(() => grafikleriCiz(g), 150); return; }
                const r = renkler();
                const yaygin = { responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: r.kalem, boxWidth: 12, font: { size: 11 } } } } };

                [trendGrafik, durumGrafik, urunGrafik].forEach(x => x && x.destroy());

                trendGrafik = new Chart(document.getElementById('grafikTrend'), {
                    type: 'line',
                    data: {
                        labels: g.trendEtiket,
                        datasets: [
                            { label: 'Fiziksel/B2B', data: g.trendFiziksel, borderColor: '#94a3b8', backgroundColor: 'rgba(148,163,184,.08)', fill: true, tension: 0.35, pointRadius: 2, borderWidth: 2 },
                            { label: 'E-Ticaret', data: g.trendEticaret, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.10)', fill: true, tension: 0.35, pointRadius: 2, borderWidth: 2 },
                        ],
                    },
                    options: { ...yaygin,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { ticks: { color: r.kalem, maxTicksLimit: 10, font: { size: 10 } }, grid: { display: false } },
                            y: { ticks: { color: r.kalem, callback: fmtKisa, font: { size: 10 } }, grid: { color: r.izgara } },
                        },
                        plugins: { legend: { labels: { color: r.kalem, boxWidth: 12, font: { size: 11 } } },
                            tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + new Intl.NumberFormat('tr-TR').format(c.parsed.y) + ' ₺' } } },
                    },
                });

                const d = g.durumDagilim;
                durumGrafik = new Chart(document.getElementById('grafikDurum'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Hazırlanıyor', 'Kargoya Verildi', 'Teslim Edildi', 'İade / Sorunlu'],
                        datasets: [{ data: [d.hazirlaniyor, d.kargoda, d.teslim, d.sorunlu],
                            backgroundColor: ['#6366f1', '#38bdf8', '#10b981', '#f59e0b'], borderWidth: 2,
                            borderColor: temaKoyuMu() ? '#1e293b' : '#ffffff' }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { color: r.kalem, boxWidth: 12, font: { size: 11 } } } } },
                });

                const urunler = g.enCokSatan;
                urunGrafik = new Chart(document.getElementById('grafikUrun'), {
                    type: 'bar',
                    data: {
                        labels: urunler.map(u => u.ad.length > 22 ? u.ad.slice(0, 21) + '…' : u.ad),
                        datasets: [{ label: 'Adet', data: urunler.map(u => u.adet),
                            backgroundColor: 'rgba(99,102,241,.75)', hoverBackgroundColor: '#6366f1',
                            borderRadius: 6, maxBarThickness: 38 }],
                    },
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false },
                            tooltip: { callbacks: { afterLabel: (c) => new Intl.NumberFormat('tr-TR').format(urunler[c.dataIndex].tutar) + ' ₺' } } },
                        scales: {
                            x: { ticks: { color: r.kalem, font: { size: 10 } }, grid: { display: false } },
                            y: { beginAtZero: true, ticks: { color: r.kalem, precision: 0, font: { size: 10 } }, grid: { color: r.izgara } },
                        } },
                });
            }

            document.addEventListener('DOMContentLoaded', () => grafikleriCiz(window.__panelBaslangic?.grafik));
            // Alpine dönem değişiminde yeni veriyi olayla taşır
            document.addEventListener('panel-veriler', (e) => grafikleriCiz(e.detail?.grafik ?? window.__panelBaslangic?.grafik));

            new MutationObserver(() => grafikleriCiz(window.__panelBaslangic?.grafik)).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        })();

        // Canlı sayaçlar: 30 sn'de bir bekleyen/onaylı/sevk sayaçlarını tazeler
        document.addEventListener('DOMContentLoaded', function () {
            const sayacGetir = () => fetch('/panel/ozet', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => { if (!r.ok) throw 0; return r.json(); })
                .then(d => {
                    const v = d.data;
                    document.querySelectorAll('[data-ozet]').forEach(el => {
                        const harita = { bekleyen: v.bekleyen, onayli: v.onayli, sevk_bugun: v.sevk_bugun, sevk_hafta: v.sevk_hafta };
                        if (harita[el.dataset.ozet] !== undefined) el.textContent = harita[el.dataset.ozet];
                    });
                })
                .catch(() => {});
            sayacGetir();
            setInterval(sayacGetir, 30000);
        });
        </script>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* Kısa finansal rakam için koyu ipucu baloncuğu */
    .ipucu-baloncuk {
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        white-space: nowrap;
        background: #0f172a;
        color: #f8fafc;
        font-size: 12px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        padding: 5px 10px;
        border-radius: 8px;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        z-index: 40;
        box-shadow: 0 6px 16px rgba(2, 6, 23, .35);
    }
    .ipucu-baloncuk::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #0f172a;
    }
    .group:hover > .ipucu-baloncuk {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
</style>
@endpush
