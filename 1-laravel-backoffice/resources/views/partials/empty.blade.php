{{-- Boş durum bloğu: ikon + başlık + açıklama + isteğe bağlı eylem butonu (kartta açılır) --}}
@php
    $icons = [
        'invoice' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'users' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-3-3',
        'box' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'quote' => 'M8 9l3 3-3 3m5 0h3M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z',
        'cart' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        'cash' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'trend' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'swap' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        'tag' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
    ];
@endphp
<tr>
    <td colspan="{{ $colspan }}" class="!px-0 !py-0">
        <div class="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-400 dark:bg-indigo-950/60 dark:text-indigo-400">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon ?? 'box'] }}"/>
                </svg>
            </span>
            <div>
                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $title }}</div>
                <div class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-400">{{ $desc }}</div>
            </div>
            @if (! empty($action))
                <button type="button"
                        data-modal-url="{{ $actionUrl }}"
                        @if (! empty($wide)) data-modal-wide="1" @endif
                        data-modal-title="{{ $actionTitle }}"
                        class="btn-primary mt-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    {{ $action }}
                </button>
            @endif
        </div>
    </td>
</tr>
