<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bellekte xlsx üretip sahte yükleme dosyası olarak döndürür.
     */
    private function makeUpload(array $headings, array $rows): UploadedFile
    {
        $export = new class($headings, $rows) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings
        {
            public function __construct(private array $headings, private array $rows) {}

            public function headings(): array { return $this->headings; }

            public function array(): array { return $this->rows; }
        };

        $contents = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        return UploadedFile::fake()->createWithContent('aktarim.xlsx', $contents);
    }

    /** @test */
    public function cariler_excel_den_ice_aktarilir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Önceden var olan cari güncellenmeli, yenisi eklenmeli
        Contact::create([
            'company_id' => $user->company_id,
            'type' => 'musteri',
            'name' => 'Eski Market',
            'phone' => null,
        ]);

        $file = $this->makeUpload(
            ['Tip', 'Ünvan', 'Telefon', 'E-posta', 'Vergi No', 'Adres'],
            [
                ['Müşteri', 'Eski Market', '0532 999 88 77', 'yeni@market.com', '', ''],
                ['Tedarikçi', 'Yeni Tedarik A.Ş.', '0216 111 22 33', 'info@yenitedarik.com', '111222333', 'Sanayi'],
            ]
        );

        $this->post('/cariler/import', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Eski kayıt güncellendi
        $this->assertDatabaseHas('contacts', ['name' => 'Eski Market', 'phone' => '0532 999 88 77']);
        // Yeni kayıt eklendi ve tip doğru eşlendi
        $this->assertDatabaseHas('contacts', ['name' => 'Yeni Tedarik A.Ş.', 'type' => 'tedarikci']);
    }

    /** @test */
    public function urunler_excel_den_ice_aktarilir_ve_acilis_hareketi_olusur(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = $this->makeUpload(
            ['Kod', 'Ürün', 'Birim', 'Alış', 'Satış', 'Stok'],
            [['URN-100', 'Webcam HD', 'adet', 300, 550, 12]]
        );

        $this->post('/urunler/import', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product = Product::where('code', 'URN-100')->first();
        $this->assertNotNull($product);
        $this->assertSame(12, $product->stock_quantity);

        // Yeni ürün açılış hareketi üretir
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'acilis',
            'quantity' => 12,
        ]);
    }

    /** @test */
    public function stok_sayimi_yuklenince_duzeltme_hareketi_olusur(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::create([
            'company_id' => $user->company_id,
            'code' => 'URN-200',
            'name' => 'Kulaklık',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 90,
            'stock_quantity' => 30, // sistemdeki
        ]);

        // Sayım: gerçekte 25 adet bulunmuş
        $file = $this->makeUpload(
            ['Kod', 'Ürün', 'Birim', 'Sistemdeki Stok', 'Sayılan Adet', 'Fark'],
            [['URN-200', 'Kulaklık', 'adet', 30, 25, -5]]
        );

        $this->post('/urunler/stok-sayim/import', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(25, $product->fresh()->stock_quantity);

        // Fark, "Düzeltme" çıkış hareketi olarak kaydedildi
        $movement = StockMovement::where('product_id', $product->id)->where('type', 'duzeltme')->first();
        $this->assertNotNull($movement);
        $this->assertSame('cikis', $movement->direction);
        $this->assertEquals(5, (float) $movement->quantity);
        $this->assertEquals(25, (float) $movement->stock_after);
    }
}
