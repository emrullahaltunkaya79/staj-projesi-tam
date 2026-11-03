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
