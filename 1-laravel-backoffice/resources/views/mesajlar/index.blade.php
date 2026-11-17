@extends('layouts.app')

@section('title', 'Destek Mesajları')

@section('content')
<div class="mx-auto max-w-4xl px-6 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Destek Mesajları</h1>
            <p class="mt-1 text-sm text-slate-500">Mağaza iletişim formundan gelen mesajlar — okunmayanlar üstte.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="space-y-3">
        @forelse ($mesajlar as $m)
            <div class="rounded-xl border p-4 shadow-sm {{ $m->okundu_at ? 'border-slate-200 bg-white' : 'border-indigo-300 bg-indigo-50/40' }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-bold text-slate-900">
                            {{ $m->ad }}
                            @if (! $m->okundu_at)
                                <span class="ml-1 rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-black text-white">YENİ</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-500">
                            ✉️ {{ $m->eposta }}
                            @if ($m->konu) · 🏷️ {{ $m->konu }} @endif
                            · 🕐 {{ \Illuminate\Support\Carbon::parse($m->created_at)->locale('tr')->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        @unless ($m->okundu_at)
                            <form method="POST" action="{{ route('mesajlar.okundu', $m->id) }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-emerald-300 px-3 py-1 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">Okundu</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('mesajlar.sil', $m->id) }}" onsubmit="return confirm('Mesaj silinsin mi?')">
                            @csrf
                            @method('DELETE')
                            <x-icon-trash title="Mesajı sil"/>
                        </form>
                    </div>
                </div>
                <p class="mt-2.5 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $m->mesaj }}</p>
            </div>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center text-slate-400 shadow-sm">Henüz mesaj yok.</div>
        @endforelse
    </div>

    {{ $mesajlar->withQueryString()->links() }}
</div>
@endsection
