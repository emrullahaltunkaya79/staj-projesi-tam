<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Şirket kullanıcıları yönetimi — yalnızca admin rolü erişebilir
 * (route'lar 'admin' middleware'i ile korunur).
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage', User::class), 403);

        $users = User::where('company_id', auth()->user()->company_id)
            ->orderBy('id')
            ->get();

        return view('users.index', compact('users'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage', User::class), 403);

        return view('users.create', ['user' => new User(['role' => 'kullanici'])]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        User::create([
            'company_id' => $request->user()->company_id,
            'role' => $validated['role'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "{$validated['name']} kullanıcısı oluşturuldu."]);
        }

        return redirect()->route('kullanicilar.index')
            ->with('success', "{$validated['name']} kullanıcısı oluşturuldu.");
    }

    /**
     * Panel: yöneticinin başka bir kullanıcıyı düzenlemesi (ad/e-posta/şifre).
     * Rol değişimi bilinçli olarak AYRI uçtadır (kendi rolünü koruma + son yönetici kuralları).
     */
    public function edit(Request $request, User $user)
    {
        abort_unless($request->user()->can('manage', User::class), 403);
        abort_unless($user->company_id === $request->user()->company_id, 404);

        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->can('manage', User::class), 403);
        abort_unless($user->company_id === $request->user()->company_id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'name.required' => 'Ad Soyad zorunludur.',
            'email.required' => 'E-posta zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
        ]);

        $epostaDegisti = $validated['email'] !== $user->email;

        $guncelle = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // E-posta değişirse doğrulama sıfırlanır (güvenlik) — yeni doğrulama maili tetiklenir
        if ($epostaDegisti) {
            $guncelle['email_verified_at'] = null;
        }

        // Şifre alanı boş bırakılırsa şifre değişmez
        if (! empty($validated['password'])) {
            $guncelle['password'] = Hash::make($validated['password']);
        }

        $user->update($guncelle);

        if ($epostaDegisti) {
            $user->sendEmailVerificationNotification();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "{$user->name} kullanıcısı güncellendi."]);
        }

        return redirect()->route('kullanicilar.index')
            ->with('success', "{$user->name} kullanıcısı güncellendi."
                .($epostaDegisti ? ' Yeni e-posta için doğrulama bağlantısı gönderildi.' : ''));
    }

    /**
     * Kullanıcının rolünü değiştirir — yalnızca yönetici erişebilir.
     * Kurallar: kendi rolün değiştirilemez, son yönetici düşürülemez.
     */
    public function changeRole(Request $request, User $user)
    {
        abort_unless($request->user()->can('manage', User::class), 403);
        abort_unless($user->company_id === $request->user()->company_id, 404);

        if ($user->id === $request->user()->id) {
            return redirect()->route('kullanicilar.index')
                ->with('error', 'Kendi rolünüzü değiştiremezsiniz.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,kullanici'],
        ], ['role.in' => 'Geçersiz rol.']);

        // Son yöneticiyi düşürmeye çalışıyorsa engelle
        if ($user->isAdmin() && $validated['role'] === 'kullanici') {
            $adminCount = User::where('company_id', $user->company_id)
                ->where('role', 'admin')
                ->whereNull('deleted_at')
                ->count();

            if ($adminCount <= 1) {
                return redirect()->route('kullanicilar.index')
                    ->with('error', 'Şirkette en az bir yönetici bulunmalı — son yöneticinin rolü düşürülemez.');
            }
        }

        $user->update(['role' => $validated['role']]); // denetim kaydı otomatik düşer

        return redirect()->route('kullanicilar.index')
            ->with('success', "{$user->name} kullanıcısının rolü '{$user->fresh()->roleLabel()}' olarak güncellendi.");
    }

    public function destroy(Request $request, User $user)
    {
        // Route binding company filtresi yapmadığı için elle doğruluyoruz
        abort_unless($user->company_id === $request->user()->company_id, 404);

        if ($user->id === $request->user()->id) {
            return redirect()->route('kullanicilar.index')
                ->with('error', 'Kendi hesabınızı silemezsiniz.');
        }

        abort_unless($request->user()->can('delete', $user), 403);

        $name = $user->name;
        $user->delete();

        return redirect()->route('kullanicilar.index')
            ->with('success', "{$name} kullanıcısı silindi.");
    }
}
