<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelVerilerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function panel_veriler_ucu_girisle_json_doner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/panel/veriler?donem=ay')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'donem',
                    'ciro' => ['kisa', 'tam', 'fiziksel_kisa', 'eticaret_kisa', 'degisim_metin', 'degisim_tur'],
                    'siparis' => ['bekleyen', 'onayli'],
                    'kargo' => ['bugun', 'hafta', 'yolda'],
                    'net_kar' => ['kisa', 'tam', 'marj', 'marj_tur'],
                    'alacak',
                    'kasa',
                    'nakit_akisi',
                    'grafik' => ['trendEtiket', 'trendFiziksel', 'trendEticaret', 'durumDagilim', 'enCokSatan'],
                ],
            ]);
    }

    /** @test */
    public function ozel_aralik_parametreleri_islenir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/panel/veriler?donem=ozel&bas=2026-01-01&son=2026-01-07')
            ->assertOk()
            ->assertJsonPath('data.donem.bas', '2026-01-01')
            ->assertJsonPath('data.donem.son', '2026-01-07');
    }

    /** @test */
    public function bilinmeyen_donem_ay_doner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/panel/veriler?donem=gecersiz')
            ->assertOk()
            ->assertJsonPath('data.donem.bas', now()->startOfMonth()->toDateString());
    }
}
