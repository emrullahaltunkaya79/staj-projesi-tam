<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FULLTEXT yalnızca MySQL/MariaDB'de var — SQLite (test DB) bu migration'ı atlar
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            return;
        }

        // Ürün adı + marka üzerinde tam metin indeksi — LIKE '%q%' yerine
        Schema::table('products', function ($table) {
            $table->fullText(['name', 'brand'], 'products_arama_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('products', function ($table) {
            $table->dropIndex('products_arama_fulltext');
        });
    }
};
