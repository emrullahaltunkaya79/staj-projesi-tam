<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Kategori içi alt kategori (Bilgisayar → Klavye, Mouse, Monitör…) + marka/model/ölçü filtreleri için
            $table->string('alt_kategori')->nullable()->after('category_id');
            $table->index('alt_kategori');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['alt_kategori']);
            $table->dropColumn('alt_kategori');
        });
    }
};
