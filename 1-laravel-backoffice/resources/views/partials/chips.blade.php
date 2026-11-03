{{-- Aktif filtre çipleri: her çip kendi parametresini kaldırır, "Temizle" hepsini --}}
@if (! empty($chips))
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Filtreler:</span>
        @foreach ($chips as $chip)
            <a href="{{ $chip['url'] }}"
               title="Bu filtreyi kaldır"
               class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-950/70">
                {{ $chip['label'] }}: {{ $chip['value'] }}
                <span class="font-black text-indigo-400">×</span>
            </a>
        @endforeach
        <a href="{{ request()->url() }}" class="text-xs font-semibold text-slate-400 underline-offset-2 transition hover:text-red-500 hover:underline">Temizle</a>
    </div>
@endif
