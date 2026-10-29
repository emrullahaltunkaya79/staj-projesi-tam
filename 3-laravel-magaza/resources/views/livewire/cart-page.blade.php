@php $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺'; @endphp

<div>
    @if (empty($items))
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-20 text-center">
            <span class="text-5xl">🛒</span>
            <div class="text-lg font-bold text-slate-700">Sepetiniz boş</div>
            <p class="max-w-sm text-sm text-slate-400">Vitrinden ürün ekleyin — stoklar arka ofisten canlı gelir, sipariş verene kadar hiçbir şey düşmez.</p>
            <a href="{{ route('shop.home') }}" class="mt-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">Alışverişe Başla</a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-3 lg:col-span-2">
                @foreach ($items as $id => $item)
                    <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            @if (! empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('shop.product', $id) }}" class="block truncate text-[15px] font-bold text-slate-800 hover:text-indigo-600">{{ $item['name'] }}</a>
                            <div class="mt-0.5 text-sm text-slate-400">{{ $fmt($item['price']) }} / {{ $item['unit'] ?? 'adet' }}</div>
                        </div>
                        <div class="flex h-10 items-center rounded-xl border border-slate-300 bg-white">
                            <button type="button" wire:click="decrease({{ $id }})" class="h-full w-9 font-bold text-slate-500 hover:text-indigo-600">−</button>
                            <span class="w-10 text-center text-sm font-bold tabular-nums">{{ $item['qty'] }}</span>
                            <button type="button" wire:click="increase({{ $id }})" class="h-full w-9 font-bold text-slate-500 hover:text-indigo-600">+</button>
                        </div>
                        <div class="w-24 text-right text-[15px] font-black tabular-nums text-slate-900">{{ $fmt($item['price'] * $item['qty']) }}</div>
                        <button type="button" wire:click="remove({{ $id }})" title="Sepetten çıkar" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-300 transition hover:bg-red-50 hover:text-red-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="h-fit rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24">
                <div class="text-sm font-bold text-slate-800">Sipariş Özeti</div>
                <div class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between text-slate-500"><span>Ara Toplam</span><span class="font-semibold tabular-nums text-slate-700">{{ $fmt($subtotal) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>KDV (%20)</span><span class="font-semibold tabular-nums text-slate-700">{{ $fmt($tax) }}</span></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-base font-black text-slate-900"><span>Toplam</span><span class="tabular-nums">{{ $fmt($total) }}</span></div>
                </div>
                <a href="{{ route('shop.checkout') }}"
                   class="mt-5 flex h-12 w-full items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-500 active:scale-[.98]">
                    Ödeme Adımına Geç →
                </a>
                <a href="{{ route('shop.home') }}" class="mt-2 block text-center text-xs font-semibold text-slate-400 hover:text-indigo-500">Alışverişe devam et</a>
                <p class="mt-4 border-t border-slate-100 pt-3 text-[11px] leading-relaxed text-slate-400">
                    Siparişi onayladığınızda ürünler arka ofiste 30 dakika boyunca <strong>sizin adınıza rezerve</strong> tutulur.
                </p>
            </div>
        </div>
    @endif
</div>
