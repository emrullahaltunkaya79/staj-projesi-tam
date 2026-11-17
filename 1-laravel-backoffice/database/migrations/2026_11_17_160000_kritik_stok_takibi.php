<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kritik stok takibi + otomatik satınalma önerisi:
// min_stock_level        → ürün başına kritik stok eşiği (varsayılan 5 — eski davranışla uyumlu)
// supplier_id            → ürünü tedarik eden tedarikçi carisi (contacts.type = tedarikci)
// low_stock_notified_at  → kritik seviye bildirimi gönderildi damgası (stok yenilenene
//                          kadar tekrar spam gönderimi engeller; toparlanınca sıfırlanır)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('min_stock_level')->default(5)->after('stock_quantity');
            $table->foreignId('supplier_id')->nullable()->after('min_stock_level')
                ->constrained('contacts')->nullOnDelete();
            $table->timestamp('low_stock_notified_at')->nullable()->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['min_stock_level', 'low_stock_notified_at']);
        });
    }
};
