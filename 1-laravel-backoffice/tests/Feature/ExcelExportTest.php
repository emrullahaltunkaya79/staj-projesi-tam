<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cari_urun_fatura_ve_stok_sayim_listeleri_excel_olarak_iner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Excel Test Cari',
        ]);

        $expectedType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        foreach (['/cariler/export', '/urunler/export', '/urunler/stok-sayim', '/faturalar/export'] as $url) {
            $response = $this->get($url);

            $response->assertOk();
            $this->assertStringContainsString(
                $expectedType,
                $response->headers->get('Content-Type'),
                "{$url} Excel dosyası döndürmeli"
            );
            $this->assertStringContainsString(
                'attachment',
                (string) $response->headers->get('Content-Disposition'),
                "{$url} indirme başlığı döndürmeli"
            );
        }
    }
}
