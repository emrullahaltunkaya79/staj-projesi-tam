<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_kullanici_yonetimini_gorur_ve_yeni_kullanici_ekler(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/kullanicilar')->assertOk()->assertSee($admin->name);

        $this->post('/kullanicilar', [
            'name' => 'Test Personel',
            'email' => 'personel@ornek.com',
            'password' => 'parola123',
            'password_confirmation' => 'parola123',
            'role' => 'kullanici',
        ])->assertRedirect('/kullanicilar');

        $user = User::where('email', 'personel@ornek.com')->first();
        $this->assertNotNull($user);
        $this->assertSame($admin->company_id, $user->company_id);
        $this->assertSame('kullanici', $user->role);
        $this->assertTrue(Hash::check('parola123', $user->password));
    }

    /** @test */
    public function standart_kullanici_yonetim_sayfasina_giremez(): void
    {
        $user = User::factory()->create(['role' => 'kullanici']);
        $this->actingAs($user);

        $this->get('/kullanicilar')->assertForbidden();
        $this->post('/kullanicilar', [
            'name' => 'Kaçak Kullanıcı',
            'email' => 'kacak@ornek.com',
            'password' => 'parola123',
            'password_confirmation' => 'parola123',
            'role' => 'admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'kacak@ornek.com']);
    }

    /** @test */
    public function standart_kullanici_cari_silemez(): void
    {
        $user = User::factory()->create(['role' => 'kullanici']);
        $this->actingAs($user);

        $contact = Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Silinemez Cari',
        ]);

        $this->delete("/cariler/{$contact->id}")
            ->assertRedirect('/cariler')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function admin_kendi_hesabini_silemez(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->delete("/kullanicilar/{$admin->id}")
            ->assertRedirect('/kullanicilar')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /** @test */
    public function yonetici_rol_listesinden_kullaniciyi_yonetligi_yukseltebilir(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personel = User::factory()->create(['role' => 'kullanici', 'company_id' => $admin->company_id]);
        $this->actingAs($admin);

        $this->post("/kullanicilar/{$personel->id}/rol", ['role' => 'admin'])
            ->assertRedirect('/kullanicilar')
            ->assertSessionHas('success');

        $this->assertSame('admin', $personel->fresh()->role);

        // Denetim kaydı düştü mü?
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'model_type' => 'User',
            'model_id' => $personel->id,
        ]);
    }

    /** @test */
    public function yonetici_kendi_rolunu_degistiremez(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post("/kullanicilar/{$admin->id}/rol", ['role' => 'kullanici'])
            ->assertRedirect('/kullanicilar')
            ->assertSessionHas('error');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    /** @test */
    public function baska_yonetici_varken_rol_dusurulebilir_ve_son_yonetici_korunur(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personel = User::factory()->create(['role' => 'kullanici', 'company_id' => $admin->company_id]);
        $this->actingAs($admin);

        // Yöneticinin kendisi tek yönetici: kendi rolünü değiştirmesi zaten engilli (ayrı test).
        // Başka bir kullanıcıyı yönetliğe yükselt → düşür döngüsü çalışmalı:
        $this->post("/kullanicilar/{$personel->id}/rol", ['role' => 'admin'])
            ->assertSessionHas('success');
        $this->assertSame('admin', $personel->fresh()->role);

        // Artık iki yönetici var: personel'i (kendisi değil) düşürmek serbest
        $this->post("/kullanicilar/{$personel->id}/rol", ['role' => 'kullanici'])
            ->assertSessionHas('success');
        $this->assertSame('kullanici', $personel->fresh()->role);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    /** @test */
    public function standart_kullanici_rol_degistiremez(): void
    {
        $personel = User::factory()->create(['role' => 'kullanici']);
        $hedef = User::factory()->create(['role' => 'kullanici', 'company_id' => $personel->company_id]);
        $this->actingAs($personel);

        $this->post("/kullanicilar/{$hedef->id}/rol", ['role' => 'admin'])->assertForbidden();
        $this->assertSame('kullanici', $hedef->fresh()->role);
    }
}
