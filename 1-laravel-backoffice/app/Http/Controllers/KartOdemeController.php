<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Odeme\OdemeGatewayFactory;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kartla online ödeme (3D Secure) akışı:
 *  1) mağaza POST /api/v1/orders/{no}/odeme-baslat  → PSP form adresi
 *  2) müşteri PSP formunu (gerçek iyzico sayfası ya da sandbox simülasyonu) doldurur
 *  3) geri dönüşte ödeme doğrulanır → markPaid → mağaza sipariş sayfasına dönülür
 */
class KartOdemeController extends Controller
{
    /** Mağaza uç: 3DS başlatma — token + yönlendirme adresi döner. */
    public function baslat(Request $request, string $orderNo): mixed
    {
        $siparis = Order::where('company_id', $request->user()->company_id)
            ->where('order_no', $orderNo)
            ->firstOrFail();

        abort_if($siparis->status !== 'pending', 422, 'Yalnızca bekleyen siparişler için ödeme başlatılabilir.');
        abort_if($siparis->payment_method !== 'kart', 422, 'Sipariş kart ödemesine ait değil.');

        $gateway = OdemeGatewayFactory::olustur();
        $baslangic = $gateway->baslat($siparis);
        $siparis->update(['odeme_token' => $baslangic['token']]);

        return response()->json([
            'data' => [
                'odeme_url' => $baslangic['odeme_url'],
                'token' => $baslangic['token'],
                'saglayici' => $gateway->ad(),
            ],
        ]);
    }

    /** PSP / sandbox formu: gerçek iyzicoda PSP sayfasına yönlendirme, sandbox'ta kart formu. */
    public function form(string $token)
    {
        $siparis = Order::where('odeme_token', $token)->where('status', 'pending')->firstOrFail();

        if (config('services.iyzico.enabled')) {
            $gateway = OdemeGatewayFactory::olustur();
            $baslangic = $gateway->baslat($siparis);

            return redirect()->away($baslangic['odeme_url']);
        }

        $gateway = new \App\Services\Odeme\MockGateway();

        return view('odeme.3ds-form', [
            'siparis' => $siparis,
            'token' => $token,
            'smsKodu' => $gateway->simdikiKod($siparis),
            'hata' => session('hata'),
        ]);
    }

    /** Sandbox form gönderimi. */
    public function formGonder(Request $request, string $token)
    {
        $siparis = Order::where('odeme_token', $token)->where('status', 'pending')->firstOrFail();

        $request->validate([
            'kart_no' => ['required', 'digits_between:15,16'],
            'son_kullanma' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['required', 'digits_between:3,4'],
            'otp' => ['required', 'digits:6'],
        ], [
            'kart_no.required' => 'Kart numarası zorunludur.',
            'son_kullanma.required' => 'Son kullanma tarihi zorunludur.',
            'cvv.required' => 'CVV zorunludur.',
            'otp.required' => '3D Secure onay kodu zorunludur.',
        ]);

        $gateway = new \App\Services\Odeme\MockGateway();

        if (! $gateway->dogrula($siparis, ['otp' => $request->input('otp')])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('hata', '3D Secure onay kodu hatalı veya süresi geçti.');
        }

        return $this->tamamla($siparis);
    }

    /** Gerçek PSP geri dönüşü (iyzico callback) — token ile ödeme sonucu sorgulanır. */
    public function geriDonus(Request $request, string $orderNo)
    {
        $siparis = Order::where('order_no', $orderNo)->firstOrFail();
        $gateway = OdemeGatewayFactory::olustur();

        if ($siparis->status === 'pending' && $gateway->dogrula($siparis)) {
            return $this->tamamla($siparis);
        }

        return redirect()
            ->away(rtrim(env('MAGAZA_URL', 'http://127.0.0.1:3001'), '/') . "/siparis/{$siparis->order_no}")
            ->with('odeme-hata', '3D Secure ödemesi onaylanmadı.');
    }

    /** Ortak tamamlama: ödeme → fatura → mağaza sipariş sayfası. */
    private function tamamla(Order $siparis)
    {
        app(OrderService::class)->markPaid($siparis->fresh(), $siparis->company_id);

        $anahtar = $siparis->fresh()->access_key;
        $magaza = rtrim(env('MAGAZA_URL', 'http://127.0.0.1:3001'), '/');

        return redirect()->away("{$magaza}/siparis/{$siparis->order_no}?odendi=1" . ($anahtar ? "&k={$anahtar}" : ''));
    }
}
