@extends('layouts.guest')
@section('title', 'Giriş Yap')

@section('content')
    <h2 class="mb-6 text-xl font-bold text-slate-800">Giriş Yap</h2>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="email">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="input" placeholder="ornek@sirket.com">
        </div>

        <div>
            <label class="label" for="password">Şifre</label>
            <input id="password" type="password" name="password" required class="input" placeholder="••••••••">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Beni hatırla
        </label>

        <div class="text-right">
            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">Şifremi unuttum?</a>
        </div>

        <button type="submit" class="btn-primary w-full">Giriş Yap</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Hesabınız yok mu?
        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Kayıt olun</a>
    </p>
@endsection
