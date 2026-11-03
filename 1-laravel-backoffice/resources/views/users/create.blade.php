@extends('layouts.app')
@section('title', 'Yeni Kullanıcı')

@section('content')
    <form method="POST" action="{{ route('kullanicilar.store') }}" class="max-w-2xl">
        @csrf

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Şirkete Kullanıcı Ekle</h2>
            <p class="mb-6 text-sm text-slate-500">Yeni kullanıcı aynı şirketin verilerine erişir.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="name">Ad Soyad <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required class="input" placeholder="Ayşe Demir">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="email">E-posta <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required class="input" placeholder="ayse@sirket.com">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="password">Şifre <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required class="input" placeholder="En az 8 karakter">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="password_confirmation">Şifre Tekrar <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input">
                </div>

                <div class="sm:col-span-2">
                    <span class="label">Rol <span class="text-red-500">*</span></span>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="role" value="admin" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('role', 'kullanici') === 'admin')>
                            Yönetici
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700">
                            <input type="radio" name="role" value="kullanici" class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('role', 'kullanici') === 'kullanici')>
                            Kullanıcı
                        </label>
                    </div>
                    @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Kullanıcıyı Oluştur</button>
                <a href="{{ route('kullanicilar.index') }}" class="btn-secondary">İptal</a>
            </div>
        </div>
    </form>
@endsection
