@extends('layouts.app')
@section('title', 'Şifre Değiştir')

@section('content')
    <form method="POST" action="{{ route('sifre-degistir.update') }}" class="max-w-xl">
        @csrf @method('PUT')

        <div class="card p-6">
            <h2 class="mb-1 text-lg font-bold text-slate-800">Şifre Değiştir</h2>
            <p class="mb-6 text-sm text-slate-500">Şifreniz değiştiğinde diğer cihazlardaki oturumlarınız kapatılır.</p>

            <div class="space-y-5">
                <div>
                    <label class="label" for="current_password">Mevcut Şifre <span class="text-red-500">*</span></label>
                    <input type="password" id="current_password" name="current_password" required class="input">
                    @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="password">Yeni Şifre <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required class="input" placeholder="En az 8 karakter">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="password_confirmation">Yeni Şifre Tekrar <span class="text-red-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input">
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="btn-primary">Şifreyi Güncelle</button>
            </div>
        </div>
    </form>
@endsection
