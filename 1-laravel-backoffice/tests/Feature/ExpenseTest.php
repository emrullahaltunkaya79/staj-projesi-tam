<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function gider_kaydi_olusturulur_ve_raporlara_yansir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/giderler', [
            'category' => 'Kira',
            'description' => 'Test kirası',
            'amount' => 2500,
            'expense_date' => now()->toDateString(),
            'payment_type' => 'nakit',
        ])->assertRedirect('/giderler')->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'category' => 'Kira',
            'amount' => 2500,
            'payment_type' => 'nakit',
        ]);

        $this->get('/giderler')->assertOk()->assertSee('Test kirası');

        // Raporlarda gider kartı ve kasa durumu görünür
        $this->get('/raporlar')
            ->assertOk()
            ->assertSee('Toplam Gider')
            ->assertSee('Kasada (Nakit)')
            ->assertSee('2.500,00');
    }

    /** @test */
    public function standart_kullanici_gider_silemez(): void
    {
        $user = User::factory()->create(['role' => 'kullanici']);
        $this->actingAs($user);

        $expense = \App\Models\Expense::create([
            'company_id' => $user->company_id,
            'category' => 'Elektrik',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'payment_type' => 'nakit',
        ]);

        $this->delete("/giderler/{$expense->id}")
            ->assertRedirect('/giderler')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }
}
