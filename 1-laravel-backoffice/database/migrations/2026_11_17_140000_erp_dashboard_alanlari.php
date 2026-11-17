<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ERP ana dashboard için e-ticaret finansal alanları:
// kanal      → siparişin geldiği satış kanalı (web, trendyol, hepsiburada, n11…)
// kargo maliyeti ve pazaryeri komisyonu → net kâr hesabının gider kalemleri
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kanal', 30)->default('web')->after('payment_method');
            $table->decimal('kargo_maliyeti', 10, 2)->default(0)->after('kanal');
            $table->decimal('pazaryeri_komisyonu', 10, 2)->default(0)->after('kargo_maliyeti');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['kanal', 'kargo_maliyeti', 'pazaryeri_komisyonu']);
        });
    }
};
