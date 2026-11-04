<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ürün adı + marka üzerinde tam metin indeksi — LIKE '%q%' yerine
        Schema::table('products', function ($table) {
            $table->fullText(['name', 'brand'], 'products_arama_fulltext');
        });

        // Uygulama açılışında indeks sözlüğünü ısıt (ilk aramalar hızlı olsun)
        DB::statement('SET GLOBAL innodb_ft_server_stopword_table = NULL');
    }

    public function down(): void
    {
        Schema::table('products', function ($table) {
            $table->dropIndex('products_arama_fulltext');
        });
    }
};
