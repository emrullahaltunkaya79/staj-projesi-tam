@extends('layouts.app')
@section('title', 'Kullanıcılar')

@section('actions')
    <button type="button" class="btn-primary" data-modal-url="{{ route('kullanicilar.create') }}" data-modal-title="Şirkete Kullanıcı Ekle">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni Kullanıcı
    </button>
@endsection

@section('content')
    <div class="card">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Kayıt Tarihi</th>
                    <th class="!text-right">İşlem</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $user->isAdmin() ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600' }} text-xs font-bold">
                                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-slate-700">
                                        {{ $user->name }}
                                        @if ($user->id === auth()->id())
                                            <span class="ml-1 text-xs text-slate-400">(siz)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
@php
    // Kurumsal rol kutusu: tüm satırlarda aynı boyut; renk indigo/slate ailesinden
    $roleBoxClasses = 'h-8 w-36 cursor-pointer rounded-lg border py-0 pl-3 pr-2 text-xs font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500 '
        .($user->isAdmin()
            ? 'border-indigo-200 bg-indigo-50 text-indigo-700'
            : 'border-slate-300 bg-slate-50 text-slate-600');
@endphp
@if ($user->id === auth()->id())
    <select disabled
            title="Kendi rolünüzü değiştiremezsiniz"
            class="{{ $roleBoxClasses }} cursor-not-allowed opacity-70">
        <option value="admin" @selected($user->isAdmin())>Yönetici</option>
        <option value="kullanici" @selected(! $user->isAdmin())>Kullanıcı</option>
    </select>
@else
    <form method="POST" action="{{ route('kullanicilar.rol', $user) }}" class="inline-block" title="Rolü değiştirmek için listeden seçin">
        @csrf
        <select name="role" onchange="this.form.submit()" class="{{ $roleBoxClasses }}">
            <option value="admin" @selected($user->isAdmin())>Yönetici</option>
            <option value="kullanici" @selected(! $user->isAdmin())>Kullanıcı</option>
        </select>
    </form>
@endif
                        </td>
                        <td>{{ $user->created_at->format('d.m.Y') }}</td>
                        <td>
                            @if ($user->id !== auth()->id())
                                <div class="flex justify-end">
                                    <form method="POST" action="{{ route('kullanicilar.destroy', $user) }}"
                                          onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Sil</button>
                                    </form>
                                </div>
                            @else
                                <div class="text-right text-xs text-slate-300">—</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-400">
        Yöneticiler kullanıcı ekleyip silebilir ve cari/ürün kayıtlarını silabilir; standart kullanıcılar bu işlemleri yapamaz.
    </p>
@endsection
