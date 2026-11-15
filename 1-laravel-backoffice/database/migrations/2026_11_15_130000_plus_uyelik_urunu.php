<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plus üyelik ürünü — markPaid'da plus_bitis bu ürünle uzatılır. */
return new class extends Migration
{
    public function up(): void
    {
        $sirketId = \Illuminate\Support\Facades\DB::table('companies')->min('id');
        if (! $sirketId) {
            return; // boş şema (test ortamı) — şirket oluşunca panel ürünü olarak eklenebilir
        }

        \Illuminate\Support\Facades\DB::table('products')->updateOrInsert(
            ['code' => 'PLUS-1YIL', 'company_id' => (int) $sirketId],
            [
                'name' => 'HepsiBirarada Plus — 1 Yıl Üyelik',
                'unit' => 'adet',
                'purchase_price' => 0,
                'sale_price' => 199,
                'stock_quantity' => 9999,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('products')->where('code', 'PLUS-1YIL')->delete();
    }
};
