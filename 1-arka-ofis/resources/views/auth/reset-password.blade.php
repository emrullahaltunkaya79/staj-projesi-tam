@extends('layouts.guest')
@section('title', 'Şifre Sıfırla')

@section('content')
    <h2 class="mb-1 text-xl font-bold text-slate-800">Yeni Şifre Belirle</h2>
    <p class="mb-6 text-sm text-slate-500">Hesabınız için yeni bir şifre belirleyin.</p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="label" for="email">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required
                   class="input" placeholder="ornek@sirket.com">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="password">Yeni Şifre</label>
            <input id="password" type="password" name="password" required
                   class="input" placeholder="En az 8 karakter">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="password_confirmation">Şifre Tekrar</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="input">
        </div>

        <button type="submit" class="btn-primary w-full">Şifreyi Güncelle</button>
    </form>
@endsection
