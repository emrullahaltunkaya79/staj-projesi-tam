<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Panel sağlık taraması: arka ofisin bütün GET sayfaları admin oturumuyla
 * ziyaret edilir; blade/render hatası olan sayfa testi kırmızıya boyar.
 * Yeni sayfa eklenince buraya satır eklenmeli.
 */
class PanelSayfaSagligiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);

        // Temel veriler: depo, kategori, ürün, cari, fatura, sipariş
        Warehouse::create(['company_id' => $this->admin->company_id, 'name' => 'Ana Depo', 'is_default' => true]);
        Category::create(['company_id' => $this->admin->company_id, 'name' => 'Test Kategori']);
        $urun = Product::create([
            'company_id' => $this->admin->company_id,
            'name' => 'Sağlık Testi Ürünü',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 120,
            'stock_quantity' => 30,
        ]);
        $cari = Contact::create(['company_id' => $this->admin->company_id, 'type' => 'musteri', 'name' => 'Sağlık Cari', 'email' => 'saglik@ornek.com']);

        $fatura = app(InvoiceService::class)->createSaleInvoice($this->admin->company_id, [
            'contact_id' => $cari->id,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 20,
            'items' => [['product_id' => $urun->id, 'quantity' => 2, 'unit_price' => 120]],
        ]);

        app(OrderService::class)->create($this->admin->company_id, [
            'contact_id' => $cari->id,
            'external_ref' => 'SAGLIK-1',
            'tax_rate' => 20,
            'customer_email' => 'saglik@ornek.com',
            'items' => [['product_id' => $urun->id, 'quantity' => 1]],
        ]);

        Expense::create([
            'company_id' => $this->admin->company_id,
            'description' => 'Kira',
            'amount' => 5000,
            'expense_date' => now()->toDateString(),
            'category' => 'kira',
        ]);

        $this->testVerileri = [
            'urun' => $urun->id,
            'cari' => $cari->id,
            'fatura' => $fatura->id,
        ];
    }

    private array $testVerileri = [];

    /** Tüm panel GET sayfaları — her satır bir rota. */
    private function rotalar(): array
    {
        return [
            '/panel',
            '/siparisler',
            '/siparisler/1',
            '/siparisler/1/etiket',
            '/cariler',
            '/cariler/'.$this->testVerileri['cari'],
            '/urunler',
            '/urunler/'.$this->testVerileri['urun'],
            '/urunler/'.$this->testVerileri['urun'].'/edit',
            '/faturalar',
            '/faturalar/'.$this->testVerileri['fatura'],
            '/stok-hareketleri',
            '/raporlar',
            '/takvim',
            '/depolar',
            '/depolar/matris',
            '/fiyatlar',
            '/virmanlar',
            '/giderler',
            '/teklifler',
            '/tekrarlayan',
            '/tahsilatlar',
            '/api-tokenlar',
            '/etkinlikler',
            '/geri-donusum',
            '/kullanicilar',
            '/ayarlar',
            '/sistem',
            '/webhooklar',
        ];
    }

    /** @test */
    public function butun_panel_sayfalari_admin_icin_hatayla_acilmaz(): void
    {
        foreach ($this->rotalar() as $rota) {
            $yanit = $this->get($rota);
            $this->assertSame(
                200,
                $yanit->status(),
                "PANEL SAYFASI HATALI: {$rota} → ".$yanit->status(),
            );
        }
    }

    /** @test */
    public function excel_disisarmalari_dosya_dondurur(): void
    {
        foreach (['/cariler/export', '/urunler/export', '/faturalar/export'] as $rota) {
            $yanit = $this->get($rota);
            $this->assertTrue($yanit->isSuccessful(), "EXPORT HATALI: {$rota}");
        }
    }

    /** @test */
    public function oturumsuz_ziyaretci_girise_yonlendirilir(): void
    {
        auth()->logout();

        foreach (['/panel', '/siparisler', '/urunler', '/ayarlar', '/sistem'] as $rota) {
            $this->get($rota)->assertRedirect();
        }
    }

    /** Hızlı aksiyonlar: bekleyen/paid/return_requested satırlarının listede görünürlüğü */
    private function siparisOlustur(string $durum): Order
    {
        Mail::fake();
        $cari = Contact::where('company_id', $this->admin->company_id)->first()
            ?? Contact::create(['company_id' => $this->admin->company_id, 'type' => 'musteri', 'name' => 'Hızlı Aksiyon Cari']);
        $urun = Product::first() ?? Product::create([
            'company_id' => $this->admin->company_id, 'name' => 'Aksiyon Ürün', 'unit' => 'adet',
            'purchase_price' => 50, 'sale_price' => 100, 'stock_quantity' => 20,
        ]);
        $order = app(OrderService::class)->create($this->admin->company_id, [
            'contact_id' => $cari->id, 'tax_rate' => 20, 'payment_method' => 'cspr',
            'items' => [['product_id' => $urun->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        if ($durum === 'paid' || $durum === 'preparing') {
            $order = app(OrderService::class)->markPaid($order, $this->admin->company_id);
        }
        if ($durum === 'preparing') {
            $order = app(OrderService::class)->markPreparing($order, $this->admin->company_id);
        }
        if ($durum === 'return_requested') {
            $order->update(['status' => 'delivered', 'delivered_at' => now()]);
            $order = app(OrderService::class)->requestReturn($order, $this->admin->company_id, ['neden' => 'diger']);
        }

        return $order->fresh();
    }

    /** @test */
    public function hizli_aksiyon_dugmeleri_duruma_gore_listede_gorunur(): void
    {
        $pending = $this->siparisOlustur('pending');
        $paid = $this->siparisOlustur('paid');
        $iade = $this->siparisOlustur('return_requested');

        $html = $this->get('/siparisler')->getContent();

        // bekleyen: Ödendi + İptal
        $this->assertTrue((bool) preg_match('/'.$pending->order_no.'[\s\S]{0,2000}?Ödendi[\s\S]{0,600}?İptal/', $html), 'Bekleyen satırda Ödendi ve İptal görünmeli.');
        // paid: Hazırlığa Al
        $this->assertTrue((bool) preg_match('/'.$paid->order_no.'[\s\S]{0,2000}?Hazırlığa Al/', $html), 'Ödenen satırda Hazırlığa Al görünmeli.');
        // return_requested: İadeyi Onayla + Reddet
        $this->assertTrue((bool) preg_match('/'.$iade->order_no.'[\s\S]{0,2000}?İadeyi Onayla[\s\S]{0,600}?Reddet/', $html), 'İade satırında Onayla/Reddet görünmeli.');
    }

    /** @test */
    public function listeden_odendi_hizli_aksiyonu_calisir(): void
    {
        $order = $this->siparisOlustur('pending');

        $this->followingRedirects()
            ->post('/siparisler/'.$order->id.'/odendi')
            ->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->invoice_id);
    }

    /** @test */
    public function listeden_iptal_hizli_aksiyonu_calisir(): void
    {
        $order = $this->siparisOlustur('pending');

        $this->followingRedirects()
            ->post('/siparisler/'.$order->id.'/iptal')
            ->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    /** @test */
    public function listeden_hazirliga_al_hizli_aksiyonu_calisir(): void
    {
        $order = $this->siparisOlustur('paid');

        $this->followingRedirects()
            ->post('/siparisler/'.$order->id.'/hazirla')
            ->assertOk();

        $this->assertSame('preparing', $order->fresh()->status);
    }

    /** @test */
    public function listeden_iade_onay_ve_reddet_hizli_aksiyonlari_calisir(): void
    {
        $order = $this->siparisOlustur('return_requested');

        // reddet: teslime döner, talep damgalı
        $this->followingRedirects()
            ->post('/siparisler/'.$order->id.'/iade-reddet', ['not' => 'Red gerekçesi test'])
            ->assertOk();
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertTrue($order->fresh()->iade_talebi['reddedildi']);

        // yeniden iade talebi + onay: returned + stok geri
        $order->update(['status' => 'delivered']);
        $order = app(OrderService::class)->requestReturn($order, $this->admin->company_id, ['neden' => 'diger']);
        $this->followingRedirects()
            ->post('/siparisler/'.$order->id.'/iade-onay')
            ->assertOk();

        $this->assertSame('returned', $order->fresh()->status);
    }
}
