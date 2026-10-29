<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function depo_olusturulur_ve_urun_acilis_stogu_atanir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Depo oluştur
        $this->post('/depolar', ['name' => 'Test Deposu', 'code' => 'TST'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $warehouse = Warehouse::where('name', 'Test Deposu')->first();
        $this->assertNotNull($warehouse);

        // Ürün stokla açılır → ilk depoya otomatik atanır
        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Depo Test Ürün',
            'unit' => 'adet',
            'purchase_price' => 50,
            'sale_price' => 100,
            'stock_quantity' => 20,
        ]);

        $this->assertSame(20, $warehouse->stockOf($product->id));
        $this->assertSame(20, $product->totalWarehouseStock());
    }

    /** @test */
    public function depolar_arasi_transfer_calisir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $w1 = Warehouse::create(['company_id' => $user->company_id, 'name' => 'Kaynak Depo']);
        $w2 = Warehouse::create(['company_id' => $user->company_id, 'name' => 'Hedef Depo']);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Transfer Ürün', 'unit' => 'adet',
            'purchase_price' => 10, 'sale_price' => 20, 'stock_quantity' => 10,
        ]);

        // Açılış stoğu ilk depoya gitti; manuel ikinci depoya da ekle
        $w1->adjustStock($product->id, 5);

        $this->assertSame(15, $w1->stockOf($product->id));

        // 5 adet transfer
        $this->post("/depolar/{$w1->id}/transfer", [
            'to_warehouse_id' => $w2->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(10, $w1->fresh()->stockOf($product->id));
        $this->assertSame(5, $w2->fresh()->stockOf($product->id));
    }

    /** @test */
    public function yetersiz_stokta_transfer_reddedilir(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $w1 = Warehouse::create(['company_id' => $user->company_id, 'name' => 'Depo A']);
        $w2 = Warehouse::create(['company_id' => $user->company_id, 'name' => 'Depo B']);

        $product = Product::create([
            'company_id' => $user->company_id,
            'name' => 'Az Stok', 'unit' => 'adet',
            'purchase_price' => 5, 'sale_price' => 10, 'stock_quantity' => 3,
        ]);

        // Açılış 3 adet ilk depoya gitti
        $this->post("/depolar/{$w1->id}/transfer", [
            'to_warehouse_id' => $w2->id,
            'product_id' => $product->id,
            'quantity' => 10, // 3'ten fazla!
        ])->assertSessionHas('error');

        $this->assertSame(3, $w1->stockOf($product->id));
        $this->assertSame(0, $w2->stockOf($product->id));
    }
}
