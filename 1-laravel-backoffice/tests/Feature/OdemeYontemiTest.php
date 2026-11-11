<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OdemeYontemiTest extends TestCase
{
    use RefreshDatabase;

    private function magazaSunucusu(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);

        return $user;
    }

    private function siparisOlustur(User $user, ?string $yontem = null): array
    {
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'Ödeme Testi Müşterisi', 'email' => 'odeme@ornek.com']);
        $urun = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Ödeme Yöntemi Test Ürünü',
            'unit' => 'adet',
            'purchase_price' => 40,
            'sale_price' => 100,
            'stock_quantity' => 10,
        ]);

        $govde = [
            'contact_id' => $cari->id,
            'tax_rate' => 20,
            'customer_email' => 'odeme@ornek.com',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ];
        if ($yontem !== null) {
            $govde['payment_method'] = $yontem;
        }

        return [$this->postJson('/api/v1/orders', $govde), $urun];
    }

    /** @test */
    public function kapida_odeme_yontemi_siparise_kaydedilir(): void
    {
        $user = $this->magazaSunucusu();
        [$cevap] = $this->siparisOlustur($user, 'kapida');

        $cevap->assertStatus(201);
        $order = Order::latest('id')->first();
        $this->assertSame('kapida', $order->payment_method);
        $this->assertSame('Kapıda Ödeme', $order->odemeYontemiEtiketi());
    }

    /** @test */
    public function havale_yontemi_siparise_kaydedilir_ve_siparis_detayinda_doner(): void
    {
        $user = $this->magazaSunucusu();
        [$cevap] = $this->siparisOlustur($user, 'havale');

        $cevap->assertStatus(201);
        $order = Order::latest('id')->first();
        $this->assertSame('havale', $order->payment_method);

        $detay = $this->getJson("/api/v1/orders/{$order->order_no}");
        $detay->assertOk()
            ->assertJsonFragment(['payment_method' => 'havale', 'odeme_yontemi' => 'Havale / EFT']);
    }

    /** @test */
    public function yontem_secilmezse_varsayilan_cspr_olur(): void
    {
        $user = $this->magazaSunucusu();
        [$cevap] = $this->siparisOlustur($user, null);

        $cevap->assertStatus(201);
        $this->assertSame('cspr', Order::latest('id')->first()->payment_method);
    }

    /** @test */
    public function gecersiz_odeme_yontemi_dogrulama_hatasi_verir(): void
    {
        $user = $this->magazaSunucusu();
        [$cevap] = $this->siparisOlustur($user, 'kripto-kurlu');

        $cevap->assertStatus(422);
        $this->assertSame(0, Order::count());
    }

    /** @test */
    public function siparis_listesi_odeme_yontemini_doner(): void
    {
        Mail::fake();
        $user = $this->magazaSunucusu();
        $this->siparisOlustur($user, 'kapida');

        $liste = $this->getJson('/api/v1/orders');
        $liste->assertOk()->assertJsonFragment(['payment_method' => 'kapida', 'odeme_yontemi' => 'Kapıda Ödeme']);
    }

    /** @test */
    public function kapida_odeme_siparisi_odenmemis_olsa_da_kargolanir_ve_fatura_sevk_ile_kesilir(): void
    {
        Mail::fake();
        $user = $this->magazaSunucusu();
        [$cevap, $urun] = $this->siparisOlustur($user, 'kapida');
        $cevap->assertStatus(201);
        $order = Order::latest('id')->first();
        $this->assertSame('pending', $order->status);

        $this->postJson("/api/v1/orders/{$order->order_no}/shipping", [
            'carrier' => 'Yurtiçi Kargo',
            'tracking_no' => 'KD123456789',
            'status' => 'shipped', // panel kargo formunun gönderdiği gibi
        ])->assertOk();

        $order = $order->fresh();
        $this->assertSame('shipped', $order->status);
        $this->assertNotNull($order->invoice_id, 'Kapıda ödemede fatura sevk anında kesilmelidir.');
    }

    /** @test */
    public function kapida_ve_havale_siparislerde_rezerv_suresi_48_saattir(): void
    {
        $user = $this->magazaSunucusu();
        $this->siparisOlustur($user, 'kapida');
        $this->siparisOlustur($user, 'havale');

        foreach (Order::latest('id')->take(2)->get() as $order) {
            $this->assertGreaterThan(
                now()->copy()->addHours(24),
                $order->expires_at,
                'Kapıda/havale siparişlerde 30 dakikalık rezerv yetersizdir.',
            );
        }
    }
}
