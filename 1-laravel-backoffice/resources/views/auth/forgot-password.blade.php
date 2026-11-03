@extends('layouts.guest')
@section('title', 'Şifremi Unuttum')

@section('content')
    <h2 class="mb-1 text-xl font-bold text-slate-800">Şifremi Unuttum</h2>
    <p class="mb-6 text-sm text-slate-500">Hesabınızın e-posta adresini girin, size bir sıfırlama bağlantısı gönderelim.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="email">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="input" placeholder="ornek@sirket.com">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Sıfırlama Bağlantısı Gönder</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">← Giriş ekranına dön</a>
    </p>
@endsection
