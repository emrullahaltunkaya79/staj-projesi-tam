<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'paid',
                'preparing',
                'shipped',
                'delivered',
                'return_requested', // müşteri iade talebi (teslimden itibaren 14 gün)
                'returned',         // satıcı onayladı: stok geri alındı, para iadesi kaydedildi
                'cancelled',
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'preparing', 'shipped', 'delivered', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }
};
