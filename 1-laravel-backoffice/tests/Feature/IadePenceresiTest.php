<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IadePenceresiTest extends TestCase
{
    use RefreshDatabase;

    private function kargodakiSiparis(User $user): Order
    {
        $cari = Contact::create(['company_id' => $user->company_id, 'name' => 'İade Testi Müşteri']);

        return Order::create([
            'company_id' => $user->company_id,
            'contact_id' => $cari->id,
            'order_no' => 'ORD-'.uniqid(),
            'status' => 'shipped',
            'subtotal' => 100,
            'tax_rate' => 20,
            'tax_amount' => 20,
            'total' => 120,
            'items' => [],
        ]);
    }

    /** @test */
    public function teslim_edilince_tarih_damgalanir_ve_iade_son_tarihi_doner(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);
        $siparis = $this->kargodakiSiparis($user);

        $siparis->update(['status' => 'delivered']);

        $this->assertNotNull($siparis->fresh()->delivered_at);
        $cevap = $this->getJson("/api/v1/orders/{$siparis->order_no}")->assertOk();
        $this->assertSame(
            $siparis->fresh()->delivered_at->copy()->addDays(Order::IADE_SURESI_GUN)->endOfDay()->toIso8601String(),
            $cevap->json('data.iade_son_tarih'),
        );
    }

    /** @test */
    public function iade_suresi_icinde_talep_kabul_edilir(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);
        $siparis = $this->kargodakiSiparis($user);
        $siparis->update(['status' => 'delivered', 'delivered_at' => now()->subDays(13)]);

        $this->postJson("/api/v1/orders/{$siparis->order_no}/return-request")->assertOk();

        $this->assertSame('return_requested', $siparis->fresh()->status);
    }

    /** @test */
    public function iade_suresi_gecince_talep_reddedilir(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['ecommerce']);
        $siparis = $this->kargodakiSiparis($user);
        $siparis->update(['status' => 'delivered', 'delivered_at' => now()->subDays(15)]);

        $this->postJson("/api/v1/orders/{$siparis->order_no}/return-request")
            ->assertStatus(422)
            ->assertJsonValidationErrors('order');

        $this->assertSame('delivered', $siparis->fresh()->status);
    }
}
