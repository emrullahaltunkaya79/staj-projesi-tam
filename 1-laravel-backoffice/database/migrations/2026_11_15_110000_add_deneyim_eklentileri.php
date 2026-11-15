<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Varsayılan teslimat adresi — checkout önceden seçili gelir
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('acik_adres');
        });

        // Kargoya verilme anı — sipariş zaman çizelgesi damgası
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('shipped_at')->nullable()->after('paid_at');
        });

        // Fiyat geçmişi — ürün detaydaki 30 günlük grafik beslenir
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipped_at');
        });
        Schema::dropIfExists('price_history');
    }
};
