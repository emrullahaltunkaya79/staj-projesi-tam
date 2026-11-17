@extends('layouts.app')
@section('title', 'Kullanıcıyı Düzenle')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-700">
                <div>
                    <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">Kullanıcıyı Düzenle</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Ad, e-posta ve şifre bilgilerini güncelleyin — rol için listedeki seçimi kullanın.</p>
                </div>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold {{ $user->isAdmin() ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                    {{ $user->roleLabel() }}
                </span>
            </div>

            <form method="POST" action="{{ route('kullanicilar.update', $user) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="label" for="name">Ad Soyad <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="email">E-posta <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="input">
                    <p class="mt-1 text-[11px] text-slate-400">
                        @if ($user->email_verified_at)
                            ✓ E-posta doğrulanmış — değiştirilirse doğrulama sıfırlanır ve yeni bağlantı gönderilir.
                        @else
                            ⏳ E-posta henüz doğrulanmamış.
                        @endif
                    </p>
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60">
                    <label class="label !mb-1" for="password">Yeni Şifre <span class="font-normal text-slate-400">(boş bırakılırsa değişmez)</span></label>
                    <input type="password" id="password" name="password" minlength="8" autocomplete="new-password"
                           class="input" placeholder="En az 8 karakter">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                    <button type="submit" class="btn-primary">Değişiklikleri Kaydet</button>
                    <a href="{{ route('kullanicilar.index') }}" class="btn-secondary">İptal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
