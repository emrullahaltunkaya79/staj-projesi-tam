{{-- Uygulama geneli sayfalama: en fazla 3 sayfa numarası (1-2-3 penceresi), chevron ile Önceki/Sonraki.
    Sayfa içinde <div class="border-t border-slate-100 px-4 py-3"> kapsayıcısıyla kullanılır. --}}
@if ($paginator->hasPages())
    @php
        // 1-2-3 penceresi: güncel sayfayı ortala, başı/sonu aşma
        $son = $paginator->lastPage();
        $guncel = $paginator->currentPage();
        $basla = max(1, min($guncel - 1, $son - 2));
        $bitis = min($son, $basla + 2);

        $numaraSinif = 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-medium transition '
            .'text-slate-600 hover:bg-slate-100 hover:text-slate-900 '
            .'dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
        $aktifSinif = 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm font-semibold text-white shadow-sm bg-indigo-600 hover:bg-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-500';
        $kenarSinif = 'inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-sm font-medium transition '
            .'text-slate-500 hover:bg-slate-100 hover:text-slate-900 '
            .'dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white';
        $kenarKapaliSinif = 'inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-sm font-medium opacity-40 cursor-not-allowed '
            .'text-slate-500 dark:text-slate-400';
    @endphp
    <nav role="navigation" aria-label="Sayfalama" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            @if (method_exists($paginator, 'total'))
                Toplam <span class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format($paginator->total(), 0, ',', '.') }}</span> kayıt
                ·
            @endif
            Sayfa <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $guncel }}/{{ $son }}</span>
        </p>

        <div class="flex items-center gap-1">
            {{-- Önceki --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $kenarKapaliSinif }}" aria-disabled="true" aria-label="Önceki sayfa">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Önceki</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Önceki sayfa" class="{{ $kenarSinif }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Önceki</span>
                </a>
            @endif

            {{-- 1-2-3 numara penceresi --}}
            @for ($i = $basla; $i <= $bitis; $i++)
                @if ($i == $guncel)
                    <span aria-current="page" class="{{ $aktifSinif }}">{{ $i }}</span>
                @else
                    <a href="{{ $paginator->url($i) }}" class="{{ $numaraSinif }}">{{ $i }}</a>
                @endif
            @endfor

            {{-- Sonraki --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Sonraki sayfa" class="{{ $kenarSinif }}">
                    <span class="hidden sm:inline">Sonraki</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="{{ $kenarKapaliSinif }}" aria-disabled="true" aria-label="Sonraki sayfa">
                    <span class="hidden sm:inline">Sonraki</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
