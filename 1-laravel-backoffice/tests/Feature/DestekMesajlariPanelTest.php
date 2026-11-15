<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Panel: iletişim formu mesajları listesi — mağazadan gelen mesaj panelde
 * görünür, okundu işaretlenir ve silinebilir.
 */
class DestekMesajlariPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Company::create(['name' => 'Mesaj Test Şirketi', 'tax_number' => '5555555555']);
        \App\Models\User::factory()->create(['email' => 'admin@test.com', 'role' => 'admin']);
    }

    private function admin(): \App\Models\User
    {
        $u = \App\Models\User::where('email', 'admin@test.com')->first();
        $this->actingAs($u);

        return $u;
    }

    /** @test */
    public function magaza_iletisim_formu_mesaji_panelde_listelenir(): void
    {
        // mağazadan gelen mesaj (gerçek akış: /api/v1/iletisim — mağaza token'ıyla)
        \App\Models\User::factory()->create(['company_id' => 1]);
        \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::where('company_id', 1)->where('role', '!=', 'musteri')->first(), ['ecommerce']);
        $this->postJson('/api/v1/iletisim', [
            'ad' => 'Deniz Yılmaz',
            'eposta' => 'deniz@ornek.com',
            'konu' => 'Siparişe özel destek',
            'mesaj' => 'Sipariş numaram: ORD-000104 — kargo ne zaman?',
        ])->assertOk();

        $this->admin();
        $yanit = $this->get(route('mesajlar.index'))->assertOk();

        $yanit->assertSee('Deniz Yılmaz');
        $yanit->assertSee('ORD-000104');
        $yanit->assertSee('YENİ');
    }

    /** @test */
    public function mesaj_okundu_isaretlenir_ve_silinir(): void
    {
        $this->admin();
        DB::table('contact_messages')->insert([
            'ad' => 'Ayşe', 'eposta' => 'ayse@ornek.test', 'konu' => 'İletişim formu',
            'mesaj' => 'Merhaba', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $id = DB::table('contact_messages')->max('id');

        $this->post(route('mesajlar.okundu', $id))->assertRedirect();
        $this->assertNotNull(DB::table('contact_messages')->where('id', $id)->value('okundu_at'));

        $this->delete(route('mesajlar.sil', $id))->assertRedirect();
        $this->assertDatabaseMissing('contact_messages', ['id' => $id]);
    }
}
