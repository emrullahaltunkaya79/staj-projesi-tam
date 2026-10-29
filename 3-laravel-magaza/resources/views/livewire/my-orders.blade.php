@php
    $fmt = fn ($v) => number_format((float) $v, 2, ',', '.').' ₺';
    $durumRenk = ['pending' => 'bg-amber-50 text-amber-700', 'paid' => 'bg-emerald-50 text-emerald-700', 'preparing' => 'bg-indigo-50 text-indigo-700', 'shipped' => 'bg-sky-50 text-sky-700', 'delivered' => 'bg-emerald-50 text-emerald-700', 'cancelled' => 'bg-red-50 text-red-600'];
    $tarih = fn ($iso) => \Carbon\Carbon::parse($iso)->format('d.m.Y H:i');
@endphp

<div class="mx-auto max-w-3xl">
    @if (empty($email))
        {{-- Hafif giriş --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl">📦</span>
            <h1 class="mt-4 text-xl font-black text-slate-900">Siparişlerim</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-400">Siparişlerinizinizi görmek için sipariş verirken kullandığınız e-posta adresinizi girin.</p>
            <form wire:submit="login" class="mx-auto mt-5 flex max-w-sm items-center gap-2">
                <input type="email" wire:model="loginEmail" placeholder="ornek@eposta.com"
                       class="h-11 flex-1 rounded-xl border border-slate-300 bg-white px-3.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 {{ $errors->has('loginEmail') ? 'border-red-400' : '' }}">
                <button type="submit" class="h-11 shrink-0 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white transition hover:bg-indigo-500 active:scale-95">Göster</button>
            </form>
            @error('loginEmail') <p class="mt-2 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
        </div>
    @else
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900">Siparişlerim</h1>
                <p class="mt-0.5 text-sm text-slate-400">{{ $name ? $name.' · ' : '' }}{{ $email }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="loadOrders" wire:loading.attr="disabled"
                        class="h-9 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-500 transition hover:border-indigo-400 hover:text-indigo-600 disabled:opacity-50">↻ Yenile</button>
                <button type="button" wire:click="logout"
                        class="h-9 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-400 transition hover:border-red-200 hover:text-red-500">Çıkış</button>
            </div>
        </div>

        @if ($error)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-800">{{ $error }}</div>
        @elseif (empty($orders))
            <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
                <span class="text-4xl">📭</span>
                <div class="text-base font-bold text-slate-700">Bu e-postayla sipariş bulunamadı</div>
                <p class="max-w-sm text-sm text-slate-400">Vitrinden alışveriş yaptığınızda siparişleriniz burada listelenir.</p>
                <a href="{{ route('shop.home') }}" class="mt-1 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">Alışverişe Başla</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($orders as $o)
                    <a href="{{ route('shop.order', $o['order_no']) }}"
                       class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $durumRenk[$o['status']] ?? 'bg-slate-100 text-slate-500' }} text-lg">
                            @if ($o['status'] === 'shipped') 🚚 @elseif ($o['status'] === 'cancelled') ✕ @elseif ($o['status'] === 'delivered') ✅ @else 🧾 @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-sm font-bold text-slate-800">{{ $o['order_no'] }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $durumRenk[$o['status']] ?? 'bg-slate-100 text-slate-500' }}">{{ $o['status_label'] ?? $o['status'] }}</span>
                                @if ($o['invoice_no'])<span class="text-[11px] text-slate-400">Fatura: {{ $o['invoice_no'] }}</span>@endif
                            </div>
                            <div class="mt-1 text-xs text-slate-400">{{ $tarih($o['created_at']) }}{{ ! empty($o['customer_name']) ? ' · '.$o['customer_name'] : '' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-base font-black tabular-nums text-slate-900">{{ $fmt($o['total']) }}</div>
                            @if (! empty($o['tracking_url']))
                                <div class="mt-0.5 text-[11px] font-bold text-sky-600">Kargoyu takip et →</div>
                            @endif
                        </div>
                        <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</div>
