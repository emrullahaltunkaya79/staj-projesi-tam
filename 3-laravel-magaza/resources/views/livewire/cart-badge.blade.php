@aware([])
<a href="{{ route('shop.cart') }}" class="relative flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-3.5 text-sm font-semibold transition hover:bg-indigo-500 active:scale-95">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    Sepet
    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-white/20 px-1 text-[11px] font-bold">{{ $count }}</span>
</a>
