<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KullaniciDuzenlemeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $personel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@firma.com']);
        $this->personel = User::factory()->create([
            'role' => 'kullanici',
            'email' => 'personel@firma.com',
            'company_id' => $this->admin->company_id,
        ]);
    }

    /** @test */
    public function yonetici_kullaniciyi_duzenleme_formunu_gorur(): void
    {
        $this->actingAs($this->admin)
            ->get("/kullanicilar/{$this->personel->id}/edit")
            ->assertOk()
            ->assertSee('Kullanıcıyı Düzenle')
            ->assertSee($this->personel->email);
    }

    /** @test */
    public function yonetici_isim_ve_epostayi_gunceller(): void
    {
        $this->actingAs($this->admin)
            ->put("/kullanicilar/{$this->personel->id}", [
                'name' => 'Yeni İsim',
                'email' => 'yeni@firma.com',
            ])
            ->assertRedirect(route('kullanicilar.index'));

        $this->assertDatabaseHas('users', [
            'id' => $this->personel->id,
            'name' => 'Yeni İsim',
            'email' => 'yeni@firma.com',
        ]);
    }

    /** @test */
    public function sifre_alani_bos_birakilirsa_degismez(): void
    {
        $eskiHash = $this->personel->password;

        $this->actingAs($this->admin)
            ->put("/kullanicilar/{$this->personel->id}", [
                'name' => $this->personel->name,
                'email' => $this->personel->email,
                'password' => '',
            ])
            ->assertRedirect(route('kullanicilar.index'));

        $this->personel->refresh();
        $this->assertSame($eskiHash, $this->personel->password);
    }

    /** @test */
    public function yeni_sifre_hashlenerek_kaydedilir(): void
    {
        $this->actingAs($this->admin)
            ->put("/kullanicilar/{$this->personel->id}", [
                'name' => $this->personel->name,
                'email' => $this->personel->email,
                'password' => 'yeni-sifre-123',
            ])
            ->assertRedirect(route('kullanicilar.index'));

        $this->personel->refresh();
        $this->assertTrue(Hash::check('yeni-sifre-123', $this->personel->password));
    }

    /** @test */
    public function baska_firmanin_kullanicisi_duzenlenemez(): void
    {
        $baskaFirma = \App\Models\Company::factory()->create();
        $yabanci = User::factory()->create(['company_id' => $baskaFirma->id]);

        $this->actingAs($this->admin)
            ->get("/kullanicilar/{$yabanci->id}/edit")
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->put("/kullanicilar/{$yabanci->id}", ['name' => 'x', 'email' => 'x@x.com'])
            ->assertNotFound();
    }

    /** @test */
    public function admin_kendi_bilgilerini_de_guncelleyebilir(): void
    {
        $this->actingAs($this->admin)
            ->put("/kullanicilar/{$this->admin->id}", [
                'name' => 'Baş Admin',
                'email' => 'admin@firma.com',
            ])
            ->assertRedirect(route('kullanicilar.index'));

        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'name' => 'Baş Admin']);
    }

    /** @test */
    public function yonetici_olmayan_erisemez(): void
    {
        $this->actingAs($this->personel)
            ->get("/kullanicilar/{$this->admin->id}/edit")
            ->assertForbidden();
    }
}
