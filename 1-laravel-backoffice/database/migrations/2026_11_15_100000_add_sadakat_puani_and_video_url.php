<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sadakat puanı: her 10 ₺'lik ödemede 1 puan kazanılır, checkout'ta 1 puan = 1 ₺ harcanır
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('sadakat_puani')->default(0)->after('balance');
        });

        // Ürün video bağlantısı (YouTube) — mağaza detayında Video sekmesi açar
        Schema::table('products', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('barcode');
        });

    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('sadakat_puani');
        });
    }
};
