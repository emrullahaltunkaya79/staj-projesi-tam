{{-- Satır içi silme ikonu: çöp kutusu figürü, hover'da rose. Silme formunun içinde kullanılır. --}}
@props(['title' => 'Sil'])
<button {{ $attributes->merge(['type' => 'submit', 'title' => $title, 'class' => 'inline-flex h-5 w-5 items-center justify-center text-slate-400 transition hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400']) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
    </svg>
    {{ $slot ?? '' }}
</button>
