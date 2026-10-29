@extends('layouts.guest')
@section('title', 'Kayıt Ol')

@section('content')
    <h2 class="mb-1 text-xl font-bold text-slate-800">Şirket Kaydı Oluştur</h2>
    <p class="mb-6 text-sm text-slate-500">Kayıt sonrası şirketinize bağlı bir kullanıcı hesabı açılır.</p>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="company_name">Şirket Adı</label>
            <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" required
                   autofocus class="input" placeholder="Örnek Ticaret Ltd. Şti.">
        </div>

        <div>
            <label class="label" for="name">Ad Soyad</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                   class="input" placeholder="Ahmet Yılmaz">
        </div>

        <div>
            <label class="label" for="email">E-posta</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="input" placeholder="ornek@sirket.com">
        </div>

        <div>
            <label class="label" for="password">Şifre</label>
            <input id="password" type="password" name="password" required
                   class="input" placeholder="En az 8 karakter">
        </div>

        <div>
            <label class="label" for="password_confirmation">Şifre Tekrar</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="input" placeholder="Şifrenizi tekrar girin">
        </div>

        <button type="submit" class="btn-primary w-full">Kayıt Ol</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Zaten hesabınız var mı?
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Giriş yapın</a>
    </p>
@endsection
