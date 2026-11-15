<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kartla online ödeme (3D Secure): payment_method enum'ına 'kart' eklenir.
 * Mevcut MySQL kurulumunda enum alter edilir; yeni kurulumlar/testler
 * 2026_11_11 migrasyonundaki genişletilmiş enum ile doğar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function ($table) {
            $table->string('odeme_token')->nullable()->after('payment_method');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cspr', 'kapida', 'havale', 'kart') NOT NULL DEFAULT 'cspr'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('orders')->where('payment_method', 'kart')->update(['payment_method' => 'cspr', 'odeme_token' => null]);
            DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cspr', 'kapida', 'havale') NOT NULL DEFAULT 'cspr'");
        }

        Schema::table('orders', function ($table) {
            $table->dropColumn('odeme_token');
        });
    }
};
