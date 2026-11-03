@extends('layouts.app')
@section('title', 'Cari Düzenle: '.$contact->name)

@section('content')
    <div class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-5 py-3">
        <div class="text-sm text-slate-500">
            Güncel bakiye:
            <span class="font-bold {{ $contact->balance > 0 ? 'text-emerald-600' : ($contact->balance < 0 ? 'text-red-600' : 'text-slate-600') }}">
                {{ number_format((float) $contact->balance, 2, ',', '.') }} ₺
            </span>
        </div>
        <span class="text-xs text-slate-400">Bakiye faturalardan otomatik güncellenir, bu ekrandan değiştirilemez.</span>
    </div>

    <form method="POST" action="{{ route('cariler.update', $contact) }}">
        @csrf @method('PUT')
        @include('contacts._form')
    </form>
@endsection
