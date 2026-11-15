<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Mağaza müşterisinin "Hesabım" işlemleri — kendi token'ıyla (tokenable = müşteri) çağrılır.
 */
class AccountApiController extends Controller
{
    /** PUT /api/v1/hesap/profil { name, phone? } */
    public function profil(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], ['name.required' => 'Ad soyad zorunludur.']);

        $user = $request->user();
        $user->update(['name' => $veri['name']]);

        Contact::where('company_id', $user->company_id)
            ->where('email', $user->email)
            ->update(['name' => $veri['name'], 'phone' => $veri['phone'] ?? null]);

        return response()->json([
            'message' => 'Profiliniz güncellendi.',
            'data' => ['name' => $user->name, 'email' => $user->email, 'phone' => $veri['phone'] ?? null],
        ]);
    }

    /** PUT /api/v1/hesap/sifre { mevcut, yeni, yeni_confirmation } */
    public function sifre(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'mevcut' => ['required', 'string'],
            'yeni' => ['required', 'string', 'min:8', 'confirmed', 'different:mevcut'],
        ], [
            'yeni.min' => 'Yeni şifre en az 8 karakter olmalıdır.',
            'yeni.confirmed' => 'Yeni şifreler eşleşmiyor.',
            'yeni.different' => 'Yeni şifre mevcut şifreden farklı olmalıdır.',
        ]);

        $user = $request->user();
        if (! Hash::check($veri['mevcut'], $user->password)) {
            throw ValidationException::withMessages(['mevcut' => 'Mevcut şifre hatalı.']);
        }

        $user->update(['password' => Hash::make($veri['yeni'])]);

        // Diğer cihazlardaki oturumlar kapanır; bu oturum açık kalır
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Şifreniz değiştirildi. Diğer cihazlardaki oturumlar kapatıldı.']);
    }

    /** GET /api/v1/hesap/adresler */
    public function adresler(Request $request): JsonResponse
    {
        return response()->json([
            'data' => DB::table('customer_addresses')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get(['id', 'baslik', 'ad_soyad', 'telefon', 'il', 'ilce', 'acik_adres', 'is_default']),
        ]);
    }

    /** POST /api/v1/hesap/adresler */
    public function adresEkle(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'baslik' => ['required', 'string', 'max:50'],
            'ad_soyad' => ['required', 'string', 'max:255'],
            'telefon' => ['required', 'string', 'max:30'],
            'il' => ['required', 'string', 'max:50'],
            'ilce' => ['required', 'string', 'max:80'],
            'acik_adres' => ['required', 'string', 'min:10', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ], ['acik_adres.min' => 'Açık adres en az 10 karakter olmalıdır.']);

        if (DB::table('customer_addresses')->where('user_id', $request->user()->id)->count() >= 10) {
            throw ValidationException::withMessages(['baslik' => 'En fazla 10 adres kaydedebilirsiniz.']);
        }

        // İlk adres otomatik varsayılan; is_default istendiyse diğerlerinin bayrağı kalkar
        $ilk = DB::table('customer_addresses')->where('user_id', $request->user()->id)->count() === 0;
        $varsayilan = $ilk || (bool) ($veri['is_default'] ?? false);
        if ($varsayilan) {
            DB::table('customer_addresses')->where('user_id', $request->user()->id)->update(['is_default' => false]);
        }
        unset($veri['is_default']);

        $id = DB::table('customer_addresses')->insertGetId($veri + [
            'user_id' => $request->user()->id,
            'is_default' => $varsayilan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Adres kaydedildi.', 'data' => ['id' => $id] + $veri], 201);
    }

    /** DELETE /api/v1/hesap/adresler/{id} */
    public function adresSil(Request $request, int $id): JsonResponse
    {
        $silinen = DB::table('customer_addresses')
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        abort_if($silinen === 0, 404, 'Adres bulunamadı.');

        return response()->json(['message' => 'Adres silindi.']);
    }

    /** POST /api/v1/hesap/adresler/{id}/varsayilan — adresi varsayılan yap */
    public function adresVarsayilan(Request $request, int $id): JsonResponse
    {
        $guncellenen = DB::table('customer_addresses')
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->update(['is_default' => true, 'updated_at' => now()]);

        abort_if($guncellenen === 0, 404, 'Adres bulunamadı.');

        // diğer adreslerin bayrağını kalkar
        DB::table('customer_addresses')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        return response()->json(['message' => 'Varsayılan adres güncellendi.']);
    }

    /**
     * KVKK m.7 — hesabı silme: kullanıcı anonimleştirilir, oturum/tokenlar kapatılır.
     * Sipariş/fatura kayıtları yasal saklama nedeniyle kalır ama kimlik bilgileri anonimleşir.
     * DELETE /api/v1/hesap
     */
    public function hesapSil(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company_id;

        // cari: faturası yoksa sil; varsa anonimleştir (yasal saklama)
        $cari = \App\Models\Contact::where('company_id', $company)
            ->where('email', $user->email)
            ->first();

        $anonimEposta = 'silinen-' . $user->id . '@anonim.local';

        if ($cari && $cari->invoices()->exists()) {
            $cari->update(['name' => 'Silinmiş Kullanıcı', 'email' => $anonimEposta, 'phone' => null, 'address' => null, 'sadakat_puani' => 0]);
        } elseif ($cari) {
            $cari->delete();
        }

        // siparişlerdeki kişisel bilgiler anonimleşir (kayıtlar yasal saklamada kalır)
        \App\Models\Order::where('customer_email', $user->email)
            ->update([
                'customer_name' => 'Silinmiş Kullanıcı',
                'customer_email' => $anonimEposta,
                'customer_phone' => null,
                'shipping_address' => null,
                'notes' => null,
            ]);

        // hesap verileri
        DB::table('customer_addresses')->where('user_id', $user->id)->delete();
        DB::table('customer_states')->where('user_id', $user->id)->delete();

        $user->tokens()->delete();
        $user->forceFill([
            'name' => 'Silinmiş Kullanıcı',
            'email' => $anonimEposta,
            'password' => \Illuminate\Support\Str::random(40),
            'email_verified_at' => null,
        ])->save();
        $user->delete(); // soft delete — geri dönüşüm kutusunda iz bırakmadan kapanır

        return response()->json(['message' => 'Hesabınız silindi. Kişisel verileriniz anonimleştirildi. Sizi ağırlamaktan dolayı teşekkür ederiz.']);
    }

    /** GET /api/v1/hesap/durum — hesaba kayıtlı favoriler ve sepet */
    public function durum(Request $request): JsonResponse
    {
        $kayit = DB::table('customer_states')->where('user_id', $request->user()->id)->first();

        $puan = \App\Models\Contact::where('company_id', $request->user()->company_id)
            ->where('email', $request->user()->email)
            ->value('sadakat_puani');

        return response()->json([
            'data' => [
                'favoriler' => $kayit ? json_decode($kayit->favoriler ?? '[]', true) : [],
                'sepet' => $kayit ? json_decode($kayit->sepet ?? '[]', true) : [],
                'sadakat_puani' => (int) ($puan ?? 0),
            ],
        ]);
    }

    /** PUT /api/v1/hesap/durum { favoriler?, sepet? } — yalnızca gönderilen alan güncellenir */
    public function durumKaydet(Request $request): JsonResponse
    {
        $veri = $request->validate([
            'favoriler' => ['sometimes', 'array', 'max:200'],
            'sepet' => ['sometimes', 'array', 'max:100'],
        ]);

        $alanlar = collect($veri)->map(fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE))->all();

        DB::table('customer_states')->updateOrInsert(
            ['user_id' => $request->user()->id],
            $alanlar + ['updated_at' => now(), 'created_at' => now()],
        );

        return response()->json(['message' => 'Kaydedildi.']);
    }
}
