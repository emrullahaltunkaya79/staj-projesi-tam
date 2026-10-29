@php
    $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
    $subtotal = \App\Services\Cart::subtotal();
    $tax = \App\Services\Cart::tax((int) $taxRate);
    $total = \App\Services\Cart::total((int) $taxRate);
    $adet = \App\Services\Cart::count();
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-black text-white">1</span>
                <div>
                    <div class="text-base font-bold text-slate-800">Teslim Bilgileri</div>
                    <div class="text-xs text-slate-400">E-postanız sizi arka ofisteki cari kartınıza otomatik bağlar</div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-500">Ad Soyad *</label>
                    <input type="text" wire:model="name" placeholder="Ayşe Yılmaz"
                           class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 {{ $errors->has('name') ? 'border-red-400' : '' }}">
                    @error('name') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-500">E-posta *</label>
                    <input type="email" wire:model="email" placeholder="ayse@ornek.com"
                           class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 {{ $errors->has('email') ? 'border-red-400' : '' }}">
                    @error('email') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-bold text-slate-500">Teslim Adresi *</label>
                    <textarea wire:model="address" rows="3" placeholder="Mahalle, cadde, no, ilçe / il"
                              class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 {{ $errors->has('address') ? 'border-red-400' : '' }}"></textarea>
                    @error('address') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-500">KDV Oranı</label>
                    <select wire:model="taxRate" class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="20">%20 (Standart)</option>
                        <option value="10">%10 (Orta)</option>
                        <option value="1">%1 (Düşük)</option>
                        <option value="0">KDV Yok</option>
                    </select>
                </div>
            </div>

            @error('api')
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</div>
            @enderror
            @error('cart')
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">{{ $message }}</div>
            @enderror
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-sm font-black text-slate-500">2</span>
                <div>
                    <div class="text-base font-bold text-slate-800">Ödeme Yöntemi</div>
                    <div class="text-xs text-slate-400">Sipariş onayından sonra seçilir</div>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50/60 px-4 py-3 text-sm text-indigo-800">
                <span class="text-lg">⛓️</span>
                <span><strong>CSPR (Casper Network)</strong> ile ödeme — sipariş sonrası ekran gösterilecek. Diğer yöntemler yakında.</span>
            </div>
        </div>
    </div>

    <div class="h-fit rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24">
        <div class="text-sm font-bold text-slate-800">Sepet Özeti ({{ $adet }} ürün)</div>
        <div class="mt-3 max-h-44 space-y-2 overflow-y-auto pr-1">
            @foreach (\App\Services\Cart::items() as $item)
                <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="min-w-0 truncate text-slate-500">{{ $item['qty'] }} × {{ $item['name'] }}</span>
                    <span class="shrink-0 font-semibold tabular-nums text-slate-700">{{ $fmt($item['qty'] * $item['price']) }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 space-y-2.5 border-t border-slate-100 pt-4 text-sm">
            <div class="flex justify-between text-slate-500"><span>Ara Toplam</span><span class="font-semibold tabular-nums text-slate-700">{{ $fmt($subtotal) }}</span></div>
            <div class="flex justify-between text-slate-500"><span>KDV (%{{ $taxRate }})</span><span class="font-semibold tabular-nums text-slate-700">{{ $fmt($tax) }}</span></div>
            <div class="flex justify-between border-t border-slate-100 pt-3 text-base font-black text-slate-900"><span>Toplam</span><span class="tabular-nums">{{ $fmt($total) }}</span></div>
        </div>
        <button type="button" wire:click="submit" wire:loading.attr="disabled"
                class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-500 active:scale-[.98] disabled:opacity-60">
            <span wire:loading.remove wire:target="submit">Siparişi Onayla — Stokları Rezerve Et</span>
            <span wire:loading wire:target="submit">Gönderiliyor…</span>
        </button>
        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
            Onayladığınızda sipariş arka ofise iletilir, ürünler 30 dakika sizin adınıza rezerve tutulur ve
            otomatik faturalandırma ödeme onayıyla tetiklenir.
        </p>
    </div>
</div>
