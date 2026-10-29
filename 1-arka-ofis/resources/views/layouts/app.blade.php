<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
{{-- Sayfa geçişi ilerleme çizgisi --}}
<div id="page-progress"
     class="pointer-events-none fixed left-0 top-0 z-[80] h-[3px] w-0 bg-gradient-to-r from-indigo-500 via-violet-500 to-indigo-500 opacity-0 shadow-[0_0_10px_rgba(99,102,241,.7)] transition-[width,opacity] duration-200 print:hidden"></div>
<div class="min-h-screen">
    @php
        $navGroups = [
            ['label' => null, 'items' => [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
            ]],
            ['label' => 'Satış & Cari', 'items' => [
                ['route' => 'cariler.index', 'label' => 'Cariler', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-3-3'],
                ['route' => 'teklifler.index', 'label' => 'Teklifler', 'icon' => 'M8 9l3 3-3 3m5 0h3M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
                ['route' => 'siparisler.index', 'label' => 'Siparişler', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                ['route' => 'faturalar.index', 'label' => 'Faturalar', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['route' => 'tekrarlayan.index', 'label' => 'Tekrarlayan', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
            ]],
            ['label' => 'Stok', 'items' => [
                ['route' => 'urunler.index', 'label' => 'Ürünler', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['route' => 'movements.index', 'label' => 'Stok Hareketleri', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ['route' => 'depolar.index', 'label' => 'Depolar', 'icon' => 'M3 21V8l9-5 9 5v13M9 21v-6h6v6'],
                ['route' => 'fiyatlar.index', 'label' => 'Özel Fiyatlar', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
            ]],
            ['label' => 'Kasa & Rapor', 'items' => [
                ['route' => 'payments.index', 'label' => 'Tahsilatlar', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['route' => 'giderler.index', 'label' => 'Giderler', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                ['route' => 'virmanlar.index', 'label' => 'Virmanlar', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                ['route' => 'raporlar.index', 'label' => 'Raporlar', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['route' => 'takvim.index', 'label' => 'Ödeme Takvimi', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ]],
        ];

        if (auth()->user()->can('manage', \App\Models\User::class)) {
            $navGroups[] = ['label' => 'Yönetim', 'items' => [
                ['route' => 'kullanicilar.index', 'label' => 'Kullanıcılar', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['route' => 'api-tokenlar.index', 'label' => 'API Tokenları', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['route' => 'etkinlik.index', 'label' => 'Etkinlik Günlüğü', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['route' => 'geri-donusum.index', 'label' => 'Geri Dönüşüm', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
                ['route' => 'sistem.index', 'label' => 'Sistem Sağlığı', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ['route' => 'webhooklar.index', 'label' => 'Webhooklar', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['route' => 'ayarlar.edit', 'label' => 'Şirket Ayarları', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
            ]];
        }

        $isActive = fn (array $item) => request()->routeIs(explode('.', $item['route'])[0].'.*')
            || request()->routeIs(explode('.', $item['route'])[0]);
        $companyLogo = auth()->user()->company->logoUrl();
        $slug = fn ($s) => \Illuminate\Support\Str::slug($s);

        // Komut paleti (Ctrl+K) verisi: tüm sayfalar + hızlı oluşturma eylemleri
        $paletteItems = [];
        foreach ($navGroups as $group) {
            foreach ($group['items'] as $item) {
                $paletteItems[] = [
                    'label' => $item['label'],
                    'hint' => $group['label'] ?: 'Genel',
                    'icon' => $item['icon'],
                    'url' => route($item['route']),
                    'kind' => 'sayfa',
                ];
            }
        }
        foreach ([
            ['Yeni Cari Ekle', 'cariler.create', true, false],
            ['Cari İçe Aktar (Excel)', 'cariler.import.form', true, false],
            ['Yeni Ürün Ekle', 'urunler.create', true, false],
            ['Yeni Fatura Kes', 'faturalar.create', true, true],
            ['Yeni Alış Faturası', 'alislar.create', true, true],
            ['Yeni Teklif Hazırla', 'teklifler.create', true, true],
            ['Yeni Tahsilat Gir', 'payments.create', true, false],
            ['Yeni Gider Gir', 'giderler.create', true, false],
        ] as [$quickLabel, $quickRoute, $inModal, $isWide]) {
            $paletteItems[] = [
                'label' => $quickLabel,
                'hint' => $inModal ? 'Kart ile' : 'Hızlı Eylem',
                'icon' => 'M12 4v16m8-8H4',
                'url' => route($quickRoute),
                'kind' => 'eylem',
                'modal' => $inModal,
                'wide' => $isWide,
            ];
        }
        if (auth()->user()->isAdmin()) {
            $paletteItems[] = [
                'label' => 'Yeni Kullanıcı Ekle',
                'hint' => 'Kart ile',
                'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-7 3a4 4 0 11-8 0 4 4 0 018 0zm3 8a7 7 0 00-14 0',
                'url' => route('kullanicilar.create'),
                'kind' => 'eylem',
                'modal' => true,
            ];
        }
    @endphp

    <div class="flex">
        {{-- ============ SIDEBAR ============ --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-10 hidden w-64 flex-col overflow-hidden bg-slate-900 print:hidden lg:flex">

            {{-- Marka --}}
            <div class="flex h-14 shrink-0 items-center border-b border-slate-800/80 px-5">
                <div class="flex min-w-0 items-center gap-2.5">
                    @if ($companyLogo)
                        <img src="{{ $companyLogo }}" alt="" class="h-8 w-8 shrink-0 rounded-lg bg-white object-contain p-0.5 ring-1 ring-white/10">
                    @else
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-xs font-black text-white ring-1 ring-white/10">ÖM</div>
                    @endif
                    <span class="truncate text-sm font-bold text-white">{{ auth()->user()->company->name }}</span>
                </div>
            </div>

            {{-- Menü (açılır gruplar + akıcı animasyon) --}}
            <nav class="sidebar-scroll min-h-0 flex-1 overflow-y-auto px-3 pb-4 pt-3">
                @foreach ($navGroups as $group)
                    @php $groupSlug = $slug($group['label'] ?? 'main'); @endphp
                    <section class="nav-group {{ $loop->first ? '' : 'nav-group-sep' }}" data-group="{{ $groupSlug }}" style="--nav-i: {{ $loop->index }};">
                        @if ($group['label'])
                            <button type="button"
                                    class="group-toggle nav-group-head"
                                    data-group="{{ $groupSlug }}">
                                <span class="nav-label">{{ $group['label'] }}</span>
                                <svg class="group-chevron nav-chevron" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        @endif
                        <div class="group-items nav-items" data-group-items="{{ $groupSlug }}">
                            @foreach ($group['items'] as $item)
                                <a href="{{ route($item['route']) }}"
                                   title="{{ $item['label'] }}"
                                   class="nav-item {{ $isActive($item) ? 'is-active' : '' }}"
                                   style="--i: {{ $loop->index }};">
                                    <svg class="nav-item-icon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                    </svg>
                                    <span class="nav-label nav-item-text">{{ $item['label'] }}</span>
                                    @if ($isActive($item))
                                        <span class="nav-label nav-active-dot"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </nav>

        </aside>

        {{-- ============ MOBIL ÜST BAR ============ --}}
        <div class="fixed inset-x-0 top-0 z-10 bg-slate-900 print:hidden lg:hidden">
            <div class="flex h-14 items-center justify-between px-4">
                <div class="flex min-w-0 items-center gap-2">
                    @if ($companyLogo)
                        <img src="{{ $companyLogo }}" alt="" class="h-8 w-8 shrink-0 rounded-lg bg-white object-contain p-0.5">
                    @else
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-xs font-black text-white">ÖM</div>
                    @endif
                    <span class="truncate text-sm font-bold text-white">{{ auth()->user()->company->name }}</span>
                </div>
                <button type="button" id="mobile-menu-btn" aria-label="Menü" aria-expanded="false"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-300 transition hover:bg-slate-800 hover:text-white">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            <nav id="mobile-menu" class="sidebar-scroll overflow-y-auto border-t border-slate-800/80 px-3 pb-3 pt-2">
                @foreach ($navGroups as $group)
                    <div class="mb-2 last:mb-0">
                        @if ($group['label'])
                            <div class="px-2.5 pb-1 pt-1 text-[10px] font-bold uppercase tracking-[.14em] text-slate-500/90">{{ $group['label'] }}</div>
                        @endif
                        <div class="space-y-0.5">
                            @foreach ($group['items'] as $item)
                                <a href="{{ route($item['route']) }}"
                                   class="nav-item {{ $isActive($item) ? 'is-active' : '' }}">
                                    <svg class="nav-item-icon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                    <span class="nav-item-text">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="nav-item w-full">
                        <svg class="nav-item-icon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span class="nav-item-text">Çıkış Yap ({{ auth()->user()->name }})</span>
                    </button>
                </form>
            </nav>
        </div>

        {{-- ============ İÇERİK ============ --}}
        <div id="content-area"
             class="flex min-h-screen w-full min-w-0 flex-col pt-14 print:ml-0 print:pt-0 lg:ml-64 lg:pt-0">

            <header class="sticky top-14 z-[5] border-b border-slate-400 bg-slate-300/90 px-6 py-4 shadow backdrop-blur print:hidden dark:border-slate-600 dark:bg-slate-800 lg:top-0">
                <div class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
                    <h1 class="shrink-0 text-lg font-bold text-slate-900 dark:text-slate-100">@yield('title', 'Panel')</h1>

                    {{-- Global arama: sığmazsa kendi satırına iner --}}
                    <div class="relative order-last mx-auto hidden w-full min-w-0 max-w-sm md:order-none md:block md:w-auto md:flex-1">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" id="global-search" autocomplete="off"
                               placeholder="Cari, ürün veya fatura ara..."
                               class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-16 text-sm text-slate-700 shadow-sm placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 dark:focus:ring-indigo-800">
                        <button type="button" id="cmdk-trigger" title="Komut paletini aç (Ctrl+K)"
                                class="absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-0.5 rounded-md border border-slate-300 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-500 transition hover:border-indigo-400 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400">
                            Ctrl K
                        </button>
                        <div id="global-search-results"
                             class="absolute left-0 right-0 top-full z-20 mt-2 hidden max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg"></div>
                    </div>

                    {{-- Sağ küme: bildirim + saat + tema + kullanıcı (birlikte hareket eder) --}}
                    <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-2">

                    {{-- Bildirim çanı --}}
                    @php
                        $notifOverdue = app(\App\Services\ReceivableService::class)->overdueInvoices(auth()->user()->company_id);
                        $notifOverdueCount = $notifOverdue->count();
                        $notifOverdueAmount = round($notifOverdue->sum('remaining'), 2);
                        $notifLowStock = \App\Models\Product::where('stock_quantity', '<', 5)->count();
                        $notifPendingOrders = \App\Models\Order::where('status', 'pending')->count();
                        $notifTotal = $notifOverdueCount + $notifLowStock + $notifPendingOrders;
                        $notifFmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
                    @endphp
                    <div class="relative shrink-0 print:hidden">
                        <button type="button" id="notif-btn" title="Bildirimler"
                                class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-500 shadow-sm transition hover:border-indigo-400 hover:text-indigo-600 active:scale-95 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if ($notifTotal > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-black text-white shadow">{{ $notifTotal > 9 ? '9+' : $notifTotal }}</span>
                            @endif
                        </button>
                        <div id="notif-menu"
                             class="pointer-events-none absolute right-0 top-full z-30 mt-2 w-80 origin-top-right translate-y-1 scale-95 rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition-all duration-150 dark:border-slate-700 dark:bg-slate-800 print:hidden">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Bildirimler</span>
                                @if ($notifTotal > 0)
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-600 dark:bg-red-950/40 dark:text-red-400">{{ $notifTotal }} uyarı</span>
                                @endif
                            </div>
                            <div class="p-2">
                                @if ($notifTotal === 0)
                                    <div class="flex flex-col items-center gap-2 px-4 py-7 text-center">
                                        <span class="text-2xl">👍</span>
                                        <span class="text-sm font-medium text-slate-500">Her şey yolunda — aktif uyarı yok.</span>
                                    </div>
                                @else
                                    @if ($notifOverdueCount > 0)
                                        <a href="{{ route('faturalar.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-red-50 dark:hover:bg-red-950/30">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400">
                                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            </span>
                                            <span class="flex-1">
                                                <span class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Vadesi geçen fatura: {{ $notifOverdueCount }}</span>
                                                <span class="block text-xs text-slate-400">Tahsil edilemeyen {{ $notifFmt($notifOverdueAmount) }}</span>
                                            </span>
                                        </a>
                                    @endif
                                    @if ($notifLowStock > 0)
                                        <a href="{{ route('urunler.index', ['low_stock' => 1]) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-amber-50 dark:hover:bg-amber-950/30">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </span>
                                            <span class="flex-1">
                                                <span class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Kritik stok: {{ $notifLowStock }} ürün</span>
                                                <span class="block text-xs text-slate-400">Stoğu 5'in altında — sipariş önerilir</span>
                                            </span>
                                        </a>
                                    @endif
                                    @if ($notifPendingOrders > 0)
                                        <a href="{{ route('siparisler.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-indigo-50 dark:hover:bg-indigo-950/30">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                            </span>
                                            <span class="flex-1">
                                                <span class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Ödeme bekleyen sipariş: {{ $notifPendingOrders }}</span>
                                                <span class="block text-xs text-slate-400">30 dakika içinde ödenmezse iptal olur</span>
                                            </span>
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Saat + hava durumu --}}
                    <div class="hidden shrink-0 items-center gap-2 2xl:flex">
                        <div id="weather-widget"
                             class="flex h-10 w-[88px] cursor-pointer select-none items-center justify-center gap-1.5 overflow-hidden rounded-full border border-slate-300 bg-white px-3 text-xs shadow-sm transition hover:border-indigo-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                             title="Hava durumu — şehri değiştirmek için tıklayın">
                            <span class="text-slate-400">…</span>
                        </div>
                        <div class="flex h-10 w-[96px] shrink-0 flex-col items-end justify-center rounded-full border border-slate-300 bg-white px-3 text-right shadow-sm leading-tight dark:border-slate-600 dark:bg-slate-800">
                            <span id="clock-time" class="font-mono text-[13px] font-bold tabular-nums text-slate-800 dark:text-slate-100">--:--</span>
                            <span id="clock-date" class="text-[10px] capitalize text-slate-500 dark:text-slate-400">—</span>
                        </div>
                    </div>

                    {{-- Dark mode toggle --}}
                    <button type="button" id="dark-toggle"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-500 shadow-sm transition hover:border-indigo-400 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-amber-400"
                            title="Karanlık/açık tema">
                        <svg id="dark-icon-moon" class="hidden h-4 w-4 dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg id="dark-icon-sun" class="h-4 w-4 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>

                    {{-- Kullanıcı dropdown (en sağda, geliştirilmiş) --}}
                    @php
                        $userStats = [
                            'cariler' => \App\Models\Contact::count(),
                            'urunler' => \App\Models\Product::count(),
                            'kritik' => \App\Models\Product::where('stock_quantity', '<', 5)->count(),
                        ];
                    @endphp
                    <div class="relative shrink-0">
                        <button type="button" id="user-menu-btn"
                                class="group flex h-10 items-center gap-2.5 rounded-full border border-slate-300 bg-white py-1 pl-1 pr-3 shadow-sm transition hover:border-indigo-500 hover:shadow-md active:scale-95 dark:border-slate-600 dark:bg-slate-800"
                                title="{{ auth()->user()->name }}">
                            <span class="relative">
                                @if ($companyLogo)
                                    <img src="{{ $companyLogo }}" alt="{{ auth()->user()->company->name }}"
                                         class="h-8 w-8 rounded-full object-contain ring-2 ring-indigo-200 transition group-hover:ring-indigo-400 dark:ring-slate-600">
                                @else
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-xs font-bold text-white transition group-hover:from-indigo-400 group-hover:to-purple-500">
                                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                @endif
                                <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3 items-center justify-center rounded-full border-2 border-white bg-emerald-500 dark:border-slate-800"></span>
                            </span>
                            <span class="hidden text-left leading-tight sm:block">
                                <span class="block max-w-[110px] truncate text-[13px] font-bold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</span>
                                <span class="block text-[10px] font-medium text-slate-400">{{ auth()->user()->roleLabel() }}</span>
                            </span>
                            <svg class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200 group-hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div id="user-menu"
                             class="absolute right-0 top-full z-30 mt-2.5 w-80 origin-top-right overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                             style="opacity: 0; transform: translateY(-8px) scale(.96); pointer-events: none; transition: opacity .25s cubic-bezier(.4,0,.2,1), transform .25s cubic-bezier(.4,0,.2,1);">

                            {{-- Gradient başlık --}}
                            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 px-5 pb-4 pt-5">
                                <div class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/5"></div>
                                <div class="pointer-events-none absolute -bottom-6 -left-4 h-20 w-20 rounded-full bg-purple-400/10"></div>
                                <div class="relative flex items-center gap-3">
                                    @if ($companyLogo)
                                        <img src="{{ $companyLogo }}" alt="{{ auth()->user()->company->name }}"
                                             class="h-12 w-12 shrink-0 rounded-2xl bg-white/90 object-contain p-1 ring-2 ring-white/40">
                                    @else
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-lg font-black text-white ring-2 ring-white/25">
                                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-white">{{ auth()->user()->name }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                            <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold text-white">{{ auth()->user()->roleLabel() }}</span>
                                            <span class="truncate text-[11px] text-indigo-200">{{ auth()->user()->company->name }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Hızlı istatistikler --}}
                                <div class="relative mt-3 grid grid-cols-3 gap-2">
                                    <div class="rounded-lg bg-white/15 px-2 py-2 text-center">
                                        <div class="text-lg font-bold leading-none text-white">{{ $userStats['cariler'] }}</div>
                                        <div class="mt-1 text-[10px] font-semibold text-indigo-200">Cari</div>
                                    </div>
                                    <div class="rounded-lg bg-white/15 px-2 py-2 text-center">
                                        <div class="text-lg font-bold leading-none text-white">{{ $userStats['urunler'] }}</div>
                                        <div class="mt-1 text-[10px] font-semibold text-indigo-200">Ürün</div>
                                    </div>
                                    <div class="rounded-lg px-2 py-2 text-center {{ $userStats['kritik'] > 0 ? 'bg-amber-500/30' : 'bg-white/15' }}">
                                        <div class="text-lg font-bold leading-none {{ $userStats['kritik'] > 0 ? 'text-amber-200' : 'text-white' }}">{{ $userStats['kritik'] }}</div>
                                        <div class="mt-1 text-[10px] font-semibold text-indigo-200">Kritik</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Menü öğeleri --}}
                            <div class="p-2">
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-200">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
                                    </span>
                                    <span>
                                        <span class="block font-semibold">Dashboard</span>
                                        <span class="block text-[11px] text-slate-400">Genel bakış ve özet</span>
                                    </span>
                                </a>

                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('ayarlar.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-200">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </span>
                                        <span>
                                            <span class="block font-semibold">Şirket Ayarları</span>
                                            <span class="block text-[11px] text-slate-400">Logo, yedekleme, bilgiler</span>
                                        </span>
                                    </a>

                                    <a href="{{ route('etkinlik.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-200">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        </span>
                                        <span>
                                            <span class="block font-semibold">Etkinlik Günlüğü</span>
                                            <span class="block text-[11px] text-slate-400">Kim, neyi, ne zaman</span>
                                        </span>
                                    </a>

                                    <a href="{{ route('kullanicilar.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-200">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        </span>
                                        <span>
                                            <span class="block font-semibold">Kullanıcılar</span>
                                            <span class="block text-[11px] text-slate-400">Roller ve yetkiler</span>
                                        </span>
                                    </a>
                                @endif

                                <a href="{{ route('sifre-degistir') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-200">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4-2a8.001 8.001 0 00-13.6 5.6L4 18l3.4-2.4A8 8 0 0021 7z"/></svg>
                                    </span>
                                    <span>
                                        <span class="block font-semibold">Şifre Değiştir</span>
                                        <span class="block text-[11px] text-slate-400">Hesap güvenliği</span>
                                    </span>
                                </a>
                            </div>

                            {{-- Çıkış --}}
                            <div class="border-t border-slate-100 p-2 dark:border-slate-700">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="group flex w-full items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-600 hover:text-white active:scale-[.98] dark:bg-red-950/30 dark:text-red-400 dark:hover:bg-red-600 dark:hover:text-white">
                                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        Çıkış Yap
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    </div>{{-- sağ küme sonu --}}
                </div>
            </header>

            {{-- Sayfa araç çubuğu: aksiyon butonları için ayrı şerit (header'ın altında) --}}
            <div id="page-actions"
                 class="flex min-w-0 flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-100 px-6 py-2.5 empty:hidden print:hidden dark:border-slate-700 dark:bg-slate-900/50">
                @yield('actions')
            </div>

            <main class="flex-1 p-6 print:p-0">
                @if (auth()->user() && ! auth()->user()->hasVerifiedEmail())
                    <div class="mb-4 flex flex-col gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 sm:flex-row sm:items-center print:hidden">
                        <span class="flex-1">
                            <span class="font-semibold">E-posta adresiniz henüz doğrulanmadı.</span>
                            Doğrulama bağlantısı {{ auth()->user()->email }} adresine gönderildi.
                        </span>
                        <form method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn-secondary !py-1.5 !px-3 text-xs">Tekrar Gönder</button>
                        </form>
                    </div>
                @endif

                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
</div>

{{-- ============ FORM KARTI (sayfa içi modal) ============ --}}
<div id="modal-overlay" class="fixed inset-0 z-[60] hidden">
    <div class="modal-backdrop absolute inset-0 bg-slate-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-150"></div>
    <div class="modal-panel relative mx-auto mt-[8vh] flex max-h-[84vh] w-[calc(100%-2rem)] max-w-2xl translate-y-2 scale-[.98] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl transition-all duration-150 dark:border-slate-700 dark:bg-slate-800"
         role="dialog" aria-modal="true">
        <div id="modal-header" class="z-10 flex shrink-0 items-center justify-between border-b border-slate-200 bg-white/95 px-5 py-3.5 backdrop-blur dark:border-slate-700 dark:bg-slate-800/95">
            <h3 id="modal-title" class="text-sm font-bold text-slate-800 dark:text-slate-100"></h3>
            <button type="button" id="modal-close" title="Kapat (ESC)"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="modal-body" class="flex-1 overflow-y-auto p-5"></div>
        <div id="modal-footer" class="hidden shrink-0 flex-wrap items-center gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700 dark:bg-slate-900/40"></div>
    </div>
</div>

{{-- Kart açıkken yazdırma: yalnızca karttaki evrak basılır --}}
<style>
@media print {
    html.modal-open body > *:not(#modal-overlay) { display: none !important; }
    html.modal-open #modal-overlay { position: static !important; display: block !important; }
    html.modal-open #modal-overlay .modal-backdrop { display: none !important; }
    html.modal-open #modal-overlay #modal-header,
    html.modal-open #modal-overlay #modal-footer { display: none !important; }
    html.modal-open #modal-overlay .modal-panel {
        position: static !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: none !important;
        max-height: none !important;
        transform: none !important;
        opacity: 1 !important;
        overflow: visible !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #fff !important;
    }
    html.modal-open #modal-body { overflow: visible !important; padding: 0 !important; }
}
</style>

{{-- ============ KOMUT PALETİ (Ctrl+K) ============ --}}
<div id="cmdk-overlay" class="fixed inset-0 z-[60] hidden print:hidden">
    <div class="cmdk-backdrop absolute inset-0 bg-slate-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-150"></div>
    <div class="cmdk-panel relative mx-auto mt-[12vh] w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl translate-y-2 scale-[.98] opacity-0 transition-all duration-150 dark:border-slate-700 dark:bg-slate-800"
         role="dialog" aria-modal="true" aria-label="Komut paleti">
        <div class="flex items-center gap-3 border-b border-slate-200 px-4 dark:border-slate-700">
            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="cmdk-input" autocomplete="off" spellcheck="false"
                   placeholder="Sayfa ara veya eylem seç…"
                   class="h-12 w-full bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none dark:text-slate-200 dark:placeholder-slate-500">
            <kbd class="shrink-0 rounded-md border border-slate-300 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-500 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400">ESC</kbd>
        </div>
        <div id="cmdk-results" class="max-h-80 overflow-y-auto p-2"></div>
        <div class="flex items-center gap-4 border-t border-slate-200 px-4 py-2 text-[10px] text-slate-400 dark:border-slate-700">
            <span class="flex items-center gap-1"><kbd class="rounded border border-slate-300 px-1 font-mono dark:border-slate-600">↑↓</kbd> gezin</span>
            <span class="flex items-center gap-1"><kbd class="rounded border border-slate-300 px-1 font-mono dark:border-slate-600">↵</kbd> aç</span>
            <span class="ml-auto">Komut Paleti</span>
        </div>
    </div>
</div>

{{-- ============ KISAYOL YARDIMI (?) ============ --}}
<div id="shortcut-help-overlay" class="fixed inset-0 z-[60] hidden print:hidden">
    <div class="help-backdrop absolute inset-0 bg-slate-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-150"></div>
    <div class="help-panel relative mx-auto mt-[12vh] w-[calc(100%-2rem)] max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl translate-y-2 scale-[.98] opacity-0 transition-all duration-150 dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <div class="text-sm font-bold text-slate-800 dark:text-slate-100">Klavye Kısayolları</div>
            <div class="text-xs text-slate-500">Panelde daha hızı gezinin</div>
        </div>
        <div class="space-y-1.5 p-4 text-sm">
            @php
                $shortcuts = [
                    ['Ctrl + K', 'Komut paleti (sayfalar + hızlı eylemler)'],
                    ['/', 'Global aramaya odaklan'],
                    ['?', 'Bu yardım kartı'],
                    ['Alt + D', 'Karanlık / açık tema'],
                    ['ESC', 'Açık pencereyi kapat'],
                ];
            @endphp
            @foreach ($shortcuts as [$keys, $desc])
                <div class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <span class="text-slate-600 dark:text-slate-300">{{ $desc }}</span>
                    <kbd class="rounded-md border border-slate-300 bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ $keys }}</kbd>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script src="{{ asset('js/global-search.js') }}"></script>
<script src="{{ asset('js/header-widgets.js') }}"></script>
<script>
(function () {
    const sidebar = document.getElementById('sidebar');

    // ---- Açılır gruplar ----
    document.querySelectorAll('.group-toggle').forEach(btn => {
        const slug = btn.dataset.group;
        const items = document.querySelector(`[data-group-items="${slug}"]`);
        const chevron = btn.querySelector('.group-chevron');
        const key = 'nav-group-' + slug;
        const stored = localStorage.getItem(key);

        // Kaydedilmiş durum: kapalıysa collapsed class ekle
        if (stored === 'closed') {
            items.classList.add('collapsed');
            chevron.style.transform = 'rotate(-90deg)';
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const closed = items.classList.toggle('collapsed');
            chevron.style.transform = closed ? 'rotate(-90deg)' : '';
            localStorage.setItem(key, closed ? 'closed' : 'open');
        });
    });

    // ---- Kullanıcı dropdown ----
    const userBtn = document.getElementById('user-menu-btn');
    const userMenu = document.getElementById('user-menu');
    if (userBtn && userMenu) {
        const openMenu = () => {
            userMenu.style.opacity = '1';
            userMenu.style.transform = 'translateY(0) scale(1)';
            userMenu.style.pointerEvents = 'auto';
        };
        const closeMenu = () => {
            userMenu.style.opacity = '0';
            userMenu.style.transform = 'translateY(-6px) scale(.97)';
            userMenu.style.pointerEvents = 'none';
        };
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = userMenu.style.opacity === '1';
            isOpen ? closeMenu() : openMenu();
        });
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target) && e.target !== userBtn) closeMenu();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });
    }

    // ---- Bildirim çanı ----
    const notifBtn = document.getElementById('notif-btn');
    const notifMenu = document.getElementById('notif-menu');
    if (notifBtn && notifMenu) {
        const openNotif = () => {
            notifMenu.classList.remove('opacity-0', 'scale-95', 'translate-y-1', 'pointer-events-none');
        };
        const closeNotif = () => {
            notifMenu.classList.add('opacity-0', 'scale-95', 'translate-y-1', 'pointer-events-none');
        };
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const kapali = notifMenu.classList.contains('opacity-0');
            kapali ? openNotif() : closeNotif();
        });
        document.addEventListener('click', (e) => {
            if (!notifMenu.contains(e.target) && e.target !== notifBtn) closeNotif();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeNotif();
        });
    }

    // ---- Dark mode ----
    const root = document.documentElement;
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme === 'dark' || (!storedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        root.classList.add('dark');
    }
    const darkBtn = document.getElementById('dark-toggle');
    if (darkBtn) {
        darkBtn.addEventListener('click', function () {
            root.classList.toggle('dark');
            localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
        });
    }

    // ---- Mobil menü ----
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function () {
            const willOpen = mobileMenu.classList.toggle('menu-open');
            mobileBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    }

    // ---- Komut paleti (Ctrl+K) + klavye kısayolları ----
    const CMDK_ITEMS = @json($paletteItems);
    const cmdkOverlay = document.getElementById('cmdk-overlay');
    const cmdkPanel = cmdkOverlay ? cmdkOverlay.querySelector('.cmdk-panel') : null;
    const cmdkBackdrop = cmdkOverlay ? cmdkOverlay.querySelector('.cmdk-backdrop') : null;
    const cmdkInput = document.getElementById('cmdk-input');
    const cmdkResults = document.getElementById('cmdk-results');
    const helpOverlay = document.getElementById('shortcut-help-overlay');
    const helpPanel = helpOverlay ? helpOverlay.querySelector('.help-panel') : null;
    const helpBackdrop = helpOverlay ? helpOverlay.querySelector('.help-backdrop') : null;
    const globalSearchInput = document.getElementById('global-search');
    let cmdkIndex = 0;
    let overlayOpener = null; // kart kapanınca odak geri döner

    const isTyping = (target) => target instanceof HTMLElement && (
        target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT'
        || target.isContentEditable
    );

    const openCmdk = () => {
        if (!cmdkOverlay) return;
        cmdkOverlay.classList.remove('hidden');
        if (cmdkPanel) cmdkPanel.setAttribute('aria-label', 'Komut paleti');
        if (document.activeElement && document.activeElement.id !== 'cmdk-input') overlayOpener = document.activeElement;
        void cmdkOverlay.offsetWidth; // reflow: başlangıç durumu işlensin, geçiş tetiklensin
        cmdkBackdrop.classList.replace('opacity-0', 'opacity-100');
        cmdkPanel.classList.remove('translate-y-2', 'scale-[.98]', 'opacity-0');
        cmdkInput.value = '';
        renderCmdk('');
        cmdkInput.focus();
    };

    const closeCmdk = () => {
        if (!cmdkOverlay || cmdkOverlay.classList.contains('hidden')) return;
        cmdkBackdrop.classList.replace('opacity-100', 'opacity-0');
        cmdkPanel.classList.add('translate-y-2', 'scale-[.98]', 'opacity-0');
        setTimeout(() => cmdkOverlay.classList.add('hidden'), 150);
    };

    const toggleHelp = (show) => {
        if (!helpOverlay) return;
        if (show) {
            helpOverlay.classList.remove('hidden');
            void helpOverlay.offsetWidth; // reflow: geçiş güvenle tetiklensin
            helpBackdrop.classList.replace('opacity-0', 'opacity-100');
            helpPanel.classList.remove('translate-y-2', 'scale-[.98]', 'opacity-0');
        } else {
            helpBackdrop.classList.replace('opacity-100', 'opacity-0');
            helpPanel.classList.add('translate-y-2', 'scale-[.98]', 'opacity-0');
            setTimeout(() => helpOverlay.classList.add('hidden'), 150);
        }
    };

    function renderCmdk(query) {
        const q = query.trim().toLocaleLowerCase('tr');
        const matches = q
            ? CMDK_ITEMS.filter((i) => (i.label + ' ' + i.hint).toLocaleLowerCase('tr').includes(q))
            : CMDK_ITEMS;
        cmdkIndex = Math.min(cmdkIndex, Math.max(matches.length - 1, 0));
        if (!matches.length) {
            cmdkResults.innerHTML = '<div class="px-3 py-6 text-center text-sm text-slate-400">Sonuç bulunamadı.</div>';
            return;
        }
        cmdkResults.innerHTML = matches.map((item, idx) => `
            <a ${item.modal
                ? `href="#" data-modal-url="${item.url}" data-modal-title="${item.label}"${item.wide ? ' data-modal-wide="1"' : ''}`
                : `href="${item.url}"`} data-idx="${idx}"
               class="cmdk-item flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition ${idx === cmdkIndex
                   ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-200'
                   : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/50'}">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg ${item.kind === 'eylem' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${item.icon}"/></svg>
                </span>
                <span class="flex-1 truncate font-medium">${item.label}</span>
                <span class="shrink-0 text-[10px] uppercase tracking-wide text-slate-400">${item.hint}</span>
            </a>`).join('');
    }

    const cmdkSelectedUrl = () => {
        const el = cmdkResults.querySelector(`[data-idx="${cmdkIndex}"]`);
        return el ? el.getAttribute('href') : null;
    };

    const moveCmdk = (delta) => {
        const items = cmdkResults.querySelectorAll('.cmdk-item');
        if (!items.length) return;
        cmdkIndex = (cmdkIndex + delta + items.length) % items.length;
        items.forEach((el) => {
            const on = Number(el.dataset.idx) === cmdkIndex;
            el.classList.toggle('bg-indigo-50', on);
            el.classList.toggle('text-indigo-700', on);
            el.classList.toggle('dark:bg-indigo-950/50', on);
            el.classList.toggle('dark:text-indigo-200', on);
        });
        const sel = cmdkResults.querySelector(`[data-idx="${cmdkIndex}"]`);
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    };

    if (cmdkOverlay) {
        cmdkInput.addEventListener('input', () => { cmdkIndex = 0; renderCmdk(cmdkInput.value); });
        cmdkInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') { e.preventDefault(); moveCmdk(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); moveCmdk(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                const el = cmdkResults.querySelector(`[data-idx="${cmdkIndex}"]`);
                if (!el) return;
                closeCmdk();
                if (el.dataset.modalUrl) {
                    openModalForm(el.dataset.modalUrl, el.dataset.modalTitle, el.dataset.modalWide === '1', el.dataset.modalView === '1');
                } else {
                    window.location.href = el.getAttribute('href');
                }
            } else if (e.key === 'Escape') { closeCmdk(); }
        });
        cmdkBackdrop.addEventListener('click', closeCmdk);
        cmdkResults.addEventListener('click', () => setTimeout(closeCmdk, 0));
        const cmdkTrigger = document.getElementById('cmdk-trigger');
        if (cmdkTrigger) cmdkTrigger.addEventListener('click', openCmdk);
    }
    if (helpOverlay) {
        helpBackdrop.addEventListener('click', () => toggleHelp(false));
    }

    document.addEventListener('keydown', (e) => {
        // Ctrl+K / Cmd+K → komut paleti
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            cmdkOverlay && (cmdkOverlay.classList.contains('hidden') ? openCmdk() : closeCmdk());
            return;
        }
        // ESC → açık paleti / form kartını / yardım kartını kapat
        if (e.key === 'Escape') {
            if (cmdkOverlay && !cmdkOverlay.classList.contains('hidden')) { closeCmdk(); return; }
            if (modalOverlay && !modalOverlay.classList.contains('hidden')) { closeModalForm(); return; }
            if (helpOverlay && !helpOverlay.classList.contains('hidden')) { toggleHelp(false); return; }
        }
        if (isTyping(e.target)) return;
        // Alt+D → tema
        if (e.altKey && e.key.toLowerCase() === 'd') {
            e.preventDefault();
            const btn = document.getElementById('dark-toggle');
            if (btn) btn.click();
            return;
        }
        // / → global arama
        if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            e.preventDefault();
            if (globalSearchInput) globalSearchInput.focus();
            return;
        }
        // ? → kısayol yardımı
        if (e.key === '?') {
            e.preventDefault();
            toggleHelp(helpOverlay.classList.contains('hidden'));
            return;
        }
    });

    // ---- Form kartı (sayfa içi modal) ----
    const modalOverlay = document.getElementById('modal-overlay');
    const modalPanel = modalOverlay ? modalOverlay.querySelector('.modal-panel') : null;
    const modalBackdrop = modalOverlay ? modalOverlay.querySelector('.modal-backdrop') : null;
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const modalFooter = document.getElementById('modal-footer');
    let modalAbort = null;

    const showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `fixed left-1/2 top-6 z-[70] -translate-x-1/2 translate-y-[-150%] rounded-xl px-5 py-3 text-sm font-semibold shadow-xl transition-all duration-300
            ${type === 'success'
                ? 'border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-200'
                : 'border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/80 dark:text-red-200'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(() => { void toast.offsetWidth; toast.style.transform = 'translate(-50%, 0)'; });
        setTimeout(() => {
            toast.style.transform = 'translate(-50%, -150%)';
            setTimeout(() => toast.remove(), 350);
        }, 2600);
    };

    // Sayfa yenilendikten sonra modal kayıt bildirimini göster
    const pendingFlash = sessionStorage.getItem('modal-flash');
    if (pendingFlash) {
        sessionStorage.removeItem('modal-flash');
        try { const f = JSON.parse(pendingFlash); showToast(f.message, f.type); } catch (e) { /* yoksay */ }
    }

    const closeModalForm = () => {
        if (!modalOverlay || modalOverlay.classList.contains('hidden')) return;
        if (modalAbort) { modalAbort.abort(); modalAbort = null; }
        document.documentElement.classList.remove('modal-open');
        if (overlayOpener && typeof overlayOpener.focus === 'function') overlayOpener.focus();
        overlayOpener = null;
        modalBackdrop.classList.replace('opacity-100', 'opacity-0');
        modalPanel.classList.add('translate-y-2', 'scale-[.98]', 'opacity-0');
        setTimeout(() => { modalBody.innerHTML = ''; }, 150);
        setTimeout(() => modalOverlay.classList.add('hidden'), 150);
    };

    const renderModalErrors = (form, errors) => {
        form.querySelectorAll('.modal-error').forEach((el) => el.remove());
        form.querySelectorAll('.modal-input-error').forEach((el) => el.classList.remove('modal-input-error', '!border-red-400'));
        const keys = Object.keys(errors);
        if (!keys.length) return;
        const box = document.createElement('div');
        box.className = 'modal-error mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300';
        box.innerHTML = keys.flatMap((k) => errors[k].map((m) => '• ' + m)).join('<br>');
        form.prepend(box);
        Object.entries(errors).forEach(([name, msgs]) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) {
                input.classList.add('modal-input-error', '!border-red-400');
                const p = document.createElement('p');
                p.className = 'modal-error mt-1 text-xs font-semibold text-red-600';
                p.textContent = msgs[0];
                input.insertAdjacentElement('afterend', p);
            }
        });
    };

    const wireModalForm = (form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const btnLabel = btn ? btn.innerHTML : null;
            if (btn) { btn.disabled = true; btn.textContent = 'Kaydediliyor…'; }
            try {
                const res = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json().catch(() => ({}));
                    closeModalForm();
                    sessionStorage.setItem('modal-flash', JSON.stringify({
                        type: 'success',
                        message: data.message || 'Kayıt oluşturuldu.',
                    }));
                    window.location.reload();
                    return;
                }
                if (res.status === 422) {
                    const data = await res.json().catch(() => ({}));
                    renderModalErrors(form, data.errors || { _: [data.message || 'Lütfen alanları kontrol edin.'] });
                } else {
                    renderModalErrors(form, { _: ['Kaydedilemedi (' + res.status + '). Lütfen tekrar deneyin.'] });
                }
            } catch (err) {
                renderModalErrors(form, { _: ['Bağlantı hatası — tekrar deneyin.'] });
            }
            if (btn) { btn.disabled = false; btn.innerHTML = btnLabel; }
        });
    };

    const openModalForm = async (url, title, wide, forceView) => {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
        modalPanel.setAttribute('aria-label', title || 'Kart');
        overlayOpener = document.activeElement;
        void modalOverlay.offsetWidth;
        modalBackdrop.classList.replace('opacity-0', 'opacity-100');
        modalPanel.classList.remove('translate-y-2', 'scale-[.98]', 'opacity-0');
        // Geniş formlar (kalem tablolu teklif vb.) için paneli genişlet
        modalPanel.classList.remove('max-w-2xl', 'max-w-5xl');
        modalPanel.classList.add(wide ? 'max-w-5xl' : 'max-w-2xl');
        modalTitle.textContent = title || '';
        modalBody.innerHTML = '<div class="py-12 text-center text-sm text-slate-400">Yükleniyor…</div>';

        modalAbort = new AbortController();
        let html;
        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: modalAbort.signal,
            });
            html = await res.text();
        } catch (err) {
            if (err.name !== 'AbortError') {
                modalBody.innerHTML = '<div class="py-12 text-center text-sm text-red-500">Form yüklenemedi.</div>';
            }
            return;
        }

        const doc = new DOMParser().parseFromString(html, 'text/html');
        // Form varsa form modu, yoksa (veya görüntüleme zorlanmışsa) görüntüleme modu
        const form = forceView ? null : doc.querySelector('main form:not([action*="yeniden-gonder"])');
        const isView = !form;
        const contentEl = form || doc.querySelector('main');
        if (!contentEl) {
            modalBody.innerHTML = '<div class="py-12 text-center text-sm text-red-500">İçerik bulunamadı.</div>';
            return;
        }
        modalFooter.classList.add('hidden');
        modalFooter.classList.remove('flex');
        modalFooter.innerHTML = '';
        if (form) {
            form.classList.remove('max-w-2xl', 'max-w-5xl');
            // kart içinde anlamı olmayan iptal/vazgeç bağlantılarını atla (şablon indir gibi işlevsel linkler kalsın)
            form.querySelectorAll('a.btn-secondary').forEach((a) => {
                if (/^(iptal|vazgeç)/i.test(a.textContent.trim())) a.remove();
            });
        } else {
            // Görüntüleme: sayfa üstündeki aksiyonları (iade, e-posta, PDF, yazdır) kart altlığına taşı;
            // kartta anlamı olmayan "Geri Dön"ü atla (yazdırma CSS'i kart açıkken yalnız evrakı basar)
            contentEl.classList.remove('flex-1', 'p-6');
            const actions = doc.querySelector('#page-actions');
            if (actions && actions.children.length) {
                const clone = actions.cloneNode(true);
                clone.querySelectorAll('a').forEach((a) => {
                    if (a.textContent.includes('Geri Dön')) a.remove();
                });
                if (clone.children.length) {
                    [...clone.children].forEach((el) => modalFooter.appendChild(el));
                    modalFooter.classList.remove('hidden');
                    modalFooter.classList.add('flex');
                }
            }
            // Kart içi fatura bağlantıları da kartta açılsın (PDF/iade hariç)
            contentEl.querySelectorAll('a[href*="/faturalar/"]').forEach((a) => {
                const href = a.getAttribute('href');
                if (/\/pdf$/.test(href) || /\/iade/.test(href)) return;
                a.dataset.modalUrl = href;
                a.dataset.modalWide = '1';
                a.dataset.modalTitle = 'Fatura';
                a.setAttribute('href', '#');
            });
        }
        modalBody.innerHTML = '';
        modalBody.appendChild(contentEl);
        // Forma gömülü satır içi betikleri (veri/ipucu) çalıştır
        doc.querySelectorAll('main script:not([src])').forEach((oldScript) => {
            const s = document.createElement('script');
            s.textContent = oldScript.textContent;
            modalBody.appendChild(s);
        });
        // Formun bağımlı olduğu harici betikleri (kalem tablosu vb.) yükle;
        // zaten sayfada yüklü olan genel betikleri atla. Betiklerin çalışması
        // beklenir ki kendi submit dinleyicileri (boş kalem temizliği) önce
        // bağlansın, sonra AJAX dinleyicisi FormData'yı atasın.
        const loaded = new Set(
            [...document.querySelectorAll('script[src]')].map((s) => new URL(s.getAttribute('src'), location.href).href)
        );
        const scriptLoads = [];
        doc.querySelectorAll('script[src]').forEach((oldScript) => {
            const src = new URL(oldScript.getAttribute('src'), location.href).href;
            if (loaded.has(src)) return;
            const s = document.createElement('script');
            s.src = src;
            scriptLoads.push(new Promise((resolve) => { s.onload = resolve; s.onerror = resolve; }));
            modalBody.appendChild(s);
        });
        if (scriptLoads.length) await Promise.all(scriptLoads);
        if (form) {
            wireModalForm(form);
            const firstInput = form.querySelector('input:not([type="hidden"]):not([type="radio"]), select, textarea');
            if (firstInput) firstInput.focus();
        }
    };

    if (modalOverlay) {
        modalBackdrop.addEventListener('click', closeModalForm);
        const modalCloseBtn = document.getElementById('modal-close');
        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModalForm);
        // data-modal-url taşıyan her buton/bağlantı form kartını açar
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-modal-url]');
            if (!trigger) return;
            e.preventDefault();
            openModalForm(trigger.dataset.modalUrl, trigger.dataset.modalTitle, trigger.dataset.modalWide === '1', trigger.dataset.modalView === '1');
        });
    }

    // ---- Odak tuzağı: kart/palet açıkken Tab içeride kalır ----
    const focusablesIn = (root) => [...root.querySelectorAll('a[href], button:not([disabled]), input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])')]
        .filter((el) => el.offsetParent !== null);
    const activeOverlayPanel = () => {
        if (typeof modalOverlay !== 'undefined' && modalOverlay && !modalOverlay.classList.contains('hidden')) return modalPanel;
        if (typeof cmdkOverlay !== 'undefined' && cmdkOverlay && !cmdkOverlay.classList.contains('hidden')) return cmdkPanel;
        return null;
    };
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab') return;
        const panel = activeOverlayPanel();
        if (!panel) return;
        const items = focusablesIn(panel);
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        if (!panel.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
        else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    // ---- Sayfa geçişi ilerleme çizgisi ----
    const pbar = document.getElementById('page-progress');
    if (pbar) {
        const PB_FLAG = 'pb-nav-active';
        const pbStart = () => {
            try { sessionStorage.setItem(PB_FLAG, '1'); } catch (err) { /* yoksay */ }
            pbar.classList.remove('opacity-0');
            pbar.style.width = '72%';
        };
        const pbDone = () => {
            try { sessionStorage.removeItem(PB_FLAG); } catch (err) { /* yoksay */ }
            pbar.style.width = '100%';
            setTimeout(() => {
                pbar.classList.add('opacity-0');
                setTimeout(() => { pbar.style.width = '0%'; }, 250);
            }, 120);
        };
        // dahili bağlantılar
        document.addEventListener('click', (e) => {
            const a = e.target.closest('a');
            if (!a || a.dataset.modalUrl) return;
            const href = a.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript:') || a.target === '_blank' || a.hasAttribute('download')) return;
            if (href.startsWith('http') && !href.startsWith(location.origin)) return;
            pbStart();
        });
        // klasik form gönderimleri (filtre/silme; AJAX kartları defaultPrevented olur)
        document.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            pbStart();
        }, true);
        // sayfa göründüğünde (bfcache dahil) tamamla
        window.addEventListener('pageshow', () => {
            let aktif = false;
            try { aktif = sessionStorage.getItem(PB_FLAG) === '1'; } catch (err) { /* yoksay */ }
            if (aktif) pbDone();
        });
    }




    // ---- Çift gönderim koruması ----
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        setTimeout(function () {
            form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.disabled = true;
            });
        }, 0);
    }, true);
})();
</script>
@stack('scripts')
</body>
</html>
