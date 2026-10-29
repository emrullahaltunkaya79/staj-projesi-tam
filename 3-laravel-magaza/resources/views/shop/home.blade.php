@extends('layouts.shop')
@section('title', 'Ana Sayfa')

@section('content')
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
        $puan = fn ($id) => 4.2 + (crc32('p'.$id) % 8) / 10;
        $yorum = fn ($id) => 37 + (crc32('y'.$id) % 480);
        $birinci = $products[0] ?? null;
        $ikinci = $products[1] ?? null;
        $ucuncu = $products[2] ?? null;
        $oneCikanlar = array_slice($products, 0, 8);
    @endphp

    {{-- Kampanya panoları --}}
    @if (! request('q') && ! request('category'))
        <section class="mb-8 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-700 p-8 text-white lg:col-span-2 lg:p-10">
                <div class="pointer-events-none absolute -right-10 top-1/2 hidden h-72 w-72 -translate-y-1/2 rounded-2xl bg-white/10 backdrop-blur lg:block"></div>
                @if ($birinci && ! empty($birinci['image_url']))
                    <img src="{{ $birinci['image_url'] }}" alt=""
                         class="pointer-events-none absolute -right-4 top-1/2 hidden h-64 w-64 -translate-y-1/2 rounded-2xl bg-white object-contain p-4 shadow-2xl lg:block">
                @endif
                <div class="relative max-w-md">
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold tracking-wide backdrop-blur">HAFTANIN FIRSATI</span>
                    <h1 class="mt-4 text-3xl font-black leading-tight tracking-tight">{{ $birinci['name'] ?? 'Teknoloji ürünleri' }}<br>%20'ye varan indirimle</h1>
                    <p class="mt-3 text-sm leading-relaxed text-indigo-100">Sipariş anında stok rezervasyonu, ödemede otomatik fatura, 14:00'ya kadar aynı gün kargo.</p>
                    @if ($birinci)
                        <a href="{{ route('shop.product', $birinci['id']) }}" class="mt-6 inline-flex h-11 items-center rounded-xl bg-white px-6 text-sm font-bold text-indigo-700 shadow-lg transition hover:bg-indigo-50 active:scale-95">Hemen İncele →</a>
                    @endif
                </div>
            </div>
            <div class="grid grid-rows-2 gap-4">
                @foreach ([['Elektronik', 'Kablosuz özgürlük', 'Hoparlör ve kulaklıkta avantaj', 'from-violet-500 to-purple-600'], ['Ofis', 'Ofis yenilendi', 'Ergonomi ürünlerinde stoklar sınırlı', 'from-emerald-500 to-teal-600']] as [$kat, $baslik, $alt, $grad])
                    <a href="{{ route('shop.home', ['category' => $kat]) }}"
                       class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $grad }} p-6 text-white">
                        <div class="pointer-events-none absolute -bottom-8 -right-6 text-8xl opacity-15 transition group-hover:scale-110">
                            @if ($kat === 'Elektronik') <svg class="h-28 w-28" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                            @else <svg class="h-28 w-28" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg> @endif
                        </div>
                        <div class="text-[11px] font-bold uppercase tracking-widest text-white/70">{{ $kat }}</div>
                        <div class="mt-1 text-xl font-black">{{ $baslik }}</div>
                        <div class="mt-1 text-xs text-white/80">{{ $alt }}</div>
                        <div class="mt-3 text-xs font-bold underline underline-offset-4">Keşfet →</div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Güven şeridi --}}
        <section class="mb-10 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0', 'Aynı Gün Kargo', '14:00\'ya kadar siparişlerde'],
                ['M13 10V3L4 14h7v7l9-11h-7z', 'CSPR ile Ödeme', 'Kripto ile hızlı ve düşük maliyetli'],
                ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'Stok Garantisi', 'Rezervasyon 30 dakika güvenceli'],
                ['M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6', 'Kolay İade', '14 gün koşulsuz iade hakkı'],
            ] as [$ikon, $baslik, $aciklama])
                <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ikon }}"/></svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[13px] font-bold text-slate-800">{{ $baslik }}</div>
                        <div class="mt-0.5 text-[11px] leading-snug text-slate-400">{{ $aciklama }}</div>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    {{-- Ürünler --}}
    <section id="urunler">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-900">{{ request('q') || request('category') ? 'Arama Sonuçları' : 'Öne Çıkanlar' }}</h2>
                <p class="mt-0.5 text-xs text-slate-400">{{ count($products) }} ürün · stok bilgisi arka ofisten canlı gelir</p>
            </div>
        </div>

        {{-- Filtre çipleri --}}
        @if (! empty($kategoriler) || request('q'))
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <a href="{{ route('shop.home') }}"
                   class="rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ request('category') ? 'bg-white text-slate-500 ring-1 ring-slate-200 hover:text-indigo-600' : 'bg-slate-900 text-white' }}">Tümü</a>
                @foreach ($kategoriler as $kat)
                    <a href="{{ route('shop.home', array_filter(['category' => $kat, 'q' => request('q')])) }}"
                       class="rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ request('category') === $kat ? 'bg-slate-900 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200 hover:text-indigo-600' }}">{{ $kat }}</a>
                @endforeach
                @if (request('q'))
                    <span class="ml-1 inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600">
                        "{{ request('q') }}" için {{ count($products) }} sonuç
                        <a href="{{ route('shop.home', array_filter(['category' => request('category')])) }}" class="font-black text-indigo-400 hover:text-indigo-700">×</a>
                    </span>
                @endif
            </div>
        @endif

        @if ($error)
            <div class="flex flex-col items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div class="text-base font-bold text-amber-800">Arka ofise bağlanılamadı</div>
                <p class="max-w-md text-sm text-amber-700">{{ $error }}</p>
            </div>
        @elseif (empty($products))
            <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <div class="text-base font-bold text-slate-700">Sonuç bulunamadı</div>
                <p class="max-w-sm text-sm text-slate-400">Farklı bir arama deneyin veya kategori filtresini kaldırın.</p>
                <a href="{{ route('shop.home') }}" class="mt-1 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">Tüm ürünleri gör</a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $p)
                    @include('shop.partials.product-card', ['p' => $p, 'fmt' => $fmt, 'puan' => $puan, 'yorum' => $yorum])
                @endforeach
            </div>
        @endif
    </section>
@endsection
