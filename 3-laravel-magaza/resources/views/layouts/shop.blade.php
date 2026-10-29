<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ana Sayfa') · {{ config('backoffice.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">

{{-- Duyuru bandı --}}
<div class="bg-slate-900 px-4 py-2 text-center text-[12px] text-slate-300">
    <span class="font-semibold text-white">Yeni:</span> Casper Network (CSPR) ile ödeme artık bu sitede
    <span class="mx-1 text-slate-600">·</span> 14:00'ya kadar verilen siparişler aynı gün kargoda
</div>

{{-- Üst bar: hesap linkleri --}}
<div class="border-b border-slate-200 bg-white">
    <div class="mx-auto hidden max-w-7xl items-center justify-end gap-4 px-4 py-1.5 text-[11px] text-slate-400 sm:flex sm:px-6">
        <a href="{{ route('shop.myorders') }}" class="transition hover:text-indigo-600">Siparişlerim</a>
        <span class="text-slate-200">|</span>
        <span>Tedarikçi olmak istiyorum</span>
        <span class="text-slate-200">|</span>
        <span>Destek: destek@demoticaret.test</span>
    </div>

    {{-- Ana header: logo + arama + aksiyonlar --}}
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3.5 sm:px-6">
        <a href="{{ route('shop.home') }}" class="flex shrink-0 items-center gap-2.5">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-lg font-black text-white shadow-md shadow-indigo-500/20">D</span>
            <span class="hidden text-[17px] font-black tracking-tight text-slate-900 sm:block">{{ config('backoffice.name') }}<span class="text-indigo-500">.</span></span>
        </a>

        <form method="GET" action="{{ route('shop.home') }}" class="relative flex-1">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Ürün, kategori veya marka ara..."
                   class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-28 text-sm placeholder-slate-400 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/10">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="height:18px;width:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <button type="submit" class="absolute right-1.5 top-1.5 h-8 rounded-lg bg-indigo-600 px-5 text-xs font-bold text-white transition hover:bg-indigo-500 active:scale-95">Ara</button>
        </form>

        <div class="flex shrink-0 items-center gap-1.5">
            <a href="{{ route('shop.myorders') }}" title="Hesabım" class="flex h-11 w-11 flex-col items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-indigo-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="mt-0.5 text-[9px] font-bold">Hesabım</span>
            </a>
            @livewire('cart-badge')
        </div>
    </div>

    {{-- Kategori şeridi --}}
    <nav class="border-t border-slate-100">
        <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 py-2 sm:px-6">
            <a href="{{ route('shop.home') }}" class="shrink-0 rounded-lg px-3 py-1.5 text-[13px] font-bold text-slate-600 transition hover:bg-indigo-50 hover:text-indigo-600 {{ ! request('category') && ! request('q') ? 'text-indigo-600' : '' }}">Tüm Kategoriler</a>
            @foreach (['Bilgisayar', 'Elektronik', 'Kırtasiye', 'Ofis'] as $kat)
                <a href="{{ route('shop.home', ['category' => $kat]) }}"
                   class="shrink-0 rounded-lg px-3 py-1.5 text-[13px] font-semibold text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600 {{ request('category') === $kat ? 'bg-indigo-50 text-indigo-600' : '' }}">{{ $kat }}</a>
            @endforeach
            <span class="ml-auto hidden shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-600 md:flex">
                <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                Arka ofis ile canlı senkron
            </span>
        </div>
    </nav>
</div>

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    @yield('content')
    {!! $slot ?? '' !!}
</main>

<footer class="mt-20 bg-slate-900 text-slate-300">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-14 sm:px-6 md:grid-cols-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-black text-white">D</span>
                <span class="text-base font-bold text-white">{{ config('backoffice.name') }}<span class="text-indigo-400">.</span></span>
            </div>
            <p class="mt-4 text-xs leading-relaxed text-slate-400">Teknoloji ve ofis ürünlerinde güvenilir alışveriş. Stok ve sipariş altyapımız <span class="font-semibold text-indigo-300">Ön Muhasebe API</span> ile gerçek zamanlı senkronize çalışır.</p>
            <div class="mt-4 flex gap-2 text-[11px] font-bold">
                <span class="rounded-lg bg-slate-800 px-2.5 py-1.5">⛓️ CSPR ödeme</span>
                <span class="rounded-lg bg-slate-800 px-2.5 py-1.5">🔒 HMAC imzalı</span>
            </div>
        </div>
        <div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-500">Kategoriler</div>
            <ul class="mt-4 space-y-2.5 text-xs">
                @foreach (['Bilgisayar', 'Elektronik', 'Kırtasiye', 'Ofis'] as $kat)
                    <li><a href="{{ route('shop.home', ['category' => $kat]) }}" class="transition hover:text-white">{{ $kat }}</a></li>
                @endforeach
                <li><a href="{{ route('shop.home') }}" class="transition hover:text-white">Tüm Ürünler</a></li>
            </ul>
        </div>
        <div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-500">Hesabım</div>
            <ul class="mt-4 space-y-2.5 text-xs">
                <li><a href="{{ route('shop.myorders') }}" class="transition hover:text-white">Siparişlerim</a></li>
                <li><a href="{{ route('shop.cart') }}" class="transition hover:text-white">Sepetim</a></li>
                <li><a href="{{ route('shop.checkout') }}" class="transition hover:text-white">Ödeme</a></li>
            </ul>
        </div>
        <div>
            <div class="text-xs font-black uppercase tracking-widest text-slate-500">Bilgi</div>
            <ul class="mt-4 space-y-2.5 text-xs text-slate-400">
                <li>Kargo takibi: sipariş sayfanızdan</li>
                <li>İade: 14 gün koşulsuz</li>
                <li>Rezervasyon: 30 dakika garanti</li>
                <li>Destek: destek@demoticaret.test</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-800 px-4 py-5 text-center text-[11px] text-slate-500">
        © {{ date('Y') }} {{ config('backoffice.name') }} — Tüm hakları saklıdır. · Bu mağaza staj projesi kapsamında geliştirilmiştir.
    </div>
</footer>

</body>
</html>
