{{-- Satır içi düzenleme ikonu: kalem figürü, hover'da indigo. href verilirse link, verilmezse buton olarak basar. --}}
@props(['title' => 'Düzenle'])
@php
    $etiket = $attributes->has('href') ? 'a' : 'button';
    $ekstra = $etiket === 'button' ? ['type' => 'button'] : [];
@endphp
<{{ $etiket }} {{ $attributes->merge(array_merge(['title' => $title, 'class' => 'inline-flex h-5 w-5 items-center justify-center text-slate-400 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400'], $ekstra)) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
    </svg>
    {{ $slot ?? '' }}
</{{ $etiket }}>
