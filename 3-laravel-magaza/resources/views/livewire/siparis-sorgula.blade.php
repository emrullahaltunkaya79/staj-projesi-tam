<div class="mx-auto max-w-xl px-4 py-10">
    <div class="border-b border-slate-200 pb-5">
        <h1 class="text-2xl font-black tracking-tight text-slate-900">Sipariş Sorgula</h1>
        <p class="mt-2 text-sm text-slate-500">
            Üye olmadan verdiğiniz siparişi, sipariş numarası ve siparişte kullandığınız e-posta ile görüntüleyin.
        </p>
    </div>

    <form wire:submit="sorgula" class="mt-8 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-500">Sipariş No *</label>
                <input type="text" wire:model="no" placeholder="ORD-000123"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                @error('no') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-500">E-posta *</label>
                <input type="email" wire:model="email" placeholder="siparişte kullandığınız e-posta"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                @error('email') <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($hata)
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600">{{ $hata }}</p>
        @endif

        <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:bg-indigo-500 active:scale-[.98] disabled:opacity-50">
            {{ $loading ? 'Sorgulanıyor…' : 'Siparişimi göster' }}
        </button>
    </form>
</div>
