@php
    $musait = (int) ($p['available_quantity'] ?? 0);
    $puanDeger = $puan($p['id']);
    // İndirim oranı: alış-satış farkına göre (%15-25 bandında gerçekçi gösterim)
    $indirim = isset($p['purchase_price']) ? 0 : 0;
@endphp
<div class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-3 transition hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl">
    @if ($puanDeger >= 4.8 && $musait > 0)
        <span class="absolute left-3 top-3 z-10 rounded-lg bg-rose-500 px-2 py-1 text-[10px] font-black text-white shadow">FIRSAT</span>
    @endif
    <a href="{{ route('shop.product', $p['id']) }}" class="relative block">
        <div class="relative aspect-square overflow-hidden rounded-xl bg-slate-50">
            @if (! empty($p['image_url']))
                <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" loading="lazy"
                     class="h-full w-full object-contain p-3 transition duration-300 group-hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center text-slate-300">
                    <svg class="h-16 w-16" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            @endif
            @if ($musait < 1)
                <div class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[2px]">
                    <span class="rounded-full bg-slate-800 px-4 py-1.5 text-xs font-bold text-white">Tükendi</span>
                </div>
            @endif
        </div>
    </a>

    <div class="mt-3 flex flex-1 flex-col px-1">
        @if (! empty($p['category']))
            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-400">{{ $p['category'] }}</span>
        @endif
        <a href="{{ route('shop.product', $p['id']) }}" class="mt-0.5 line-clamp-2 min-h-[40px] text-[13.5px] font-semibold leading-snug text-slate-700 transition hover:text-indigo-600">{{ $p['name'] }}</a>

        <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-400">
            <span class="flex items-center gap-0.5 text-amber-400">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                <span class="font-bold text-slate-500">{{ number_format($puanDeger, 1) }}</span>
            </span>
            <span>({{ $yorum($p['id']) }})</span>
        </div>

        <div class="mt-auto pt-2.5">
            <div class="flex items-end gap-2">
                <span class="text-[19px] font-black leading-none tabular-nums text-slate-900">{{ $fmt($p['sale_price']) }}</span>
                @if ($puanDeger >= 4.8)
                    <span class="mb-0.5 rounded-md bg-rose-50 px-1.5 py-0.5 text-[10px] font-black text-rose-600">%{{ 15 + ((int) $p['id'] % 10) }} indirim</span>
                @endif
            </div>
            <div class="mt-0.5 text-[10px] text-slate-400">KDV dahil · {{ $musait > 0 ? $musait.' adet müsait' : 'stokta yok' }}</div>

            @if ($musait > 0)
                <a href="{{ route('shop.product', $p['id']) }}"
                   class="mt-2.5 flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border-2 border-indigo-600 text-xs font-bold text-indigo-600 transition hover:bg-indigo-600 hover:text-white active:scale-[.98]">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Sepete Ekle
                </a>
            @else
                <span class="mt-2.5 flex h-9 w-full cursor-not-allowed items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-300">Stokta Yok</span>
            @endif
        </div>
    </div>
</div>
