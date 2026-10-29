<div class="mt-6 flex flex-col gap-3">
    <div class="flex items-center gap-3">
        <div class="flex h-12 items-center rounded-xl border border-slate-300 bg-white">
            <button type="button" wire:click="qty--" class="h-full w-11 text-lg font-bold text-slate-500 hover:text-indigo-600">−</button>
            <input type="number" wire:model.live="qty" min="1" max="{{ $available }}"
                   class="h-full w-12 border-x border-slate-200 text-center text-sm font-bold focus:outline-none">
            <button type="button" wire:click="qty++" class="h-full w-11 text-lg font-bold text-slate-500 hover:text-indigo-600">+</button>
        </div>
        <button type="button" wire:click="add"
                {{ $available > 0 ? '' : 'disabled' }}
                class="flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-500 active:scale-[.98] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            {{ $available > 0 ? 'Sepete Ekle' : 'Stokta Yok' }}
        </button>
    </div>
    @if ($added)
        <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
            <span>✓ Sepete eklendi ({{ $qty }} {{ $unit }})</span>
            <a href="{{ route('shop.cart') }}" class="underline underline-offset-2 hover:text-emerald-800">Sepete git →</a>
        </div>
    @endif
    @error('qty') <p class="text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
</div>
