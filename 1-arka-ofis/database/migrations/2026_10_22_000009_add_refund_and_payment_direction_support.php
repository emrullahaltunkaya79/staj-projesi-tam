<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İade faturası ve tedarikçi ödemesi desteği:
 *  - invoices.type enum'una 'iade' eklenir (SQLite'ta enum değiştirilemediği
 *    için yalnızca MySQL'de change() uygulanır; taze kurulumlarda doğru enum
 *    tablo oluşturma migration'larında zaten tanımlıdır)
 *  - invoices.refunded_invoice_id: iade faturasının hangi faturayı tersine çevirdiği
 *  - payments.direction: tahsilat (müşteriden giriş) / odeme (tedarikçiye çıkış)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Enum genişletme yalnızca MySQL'de gerekli (mevcut kurulumlar için)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('invoices', function (Blueprint $table) {
                $table->enum('type', ['satis', 'alis', 'iade'])->default('satis')->change();
            });
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->enum('type', ['acilis', 'alis', 'satis', 'iade', 'duzeltme'])->change();
            });
        }

        if (! Schema::hasColumn('invoices', 'refunded_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('refunded_invoice_id')
                    ->nullable()
                    ->after('due_date')
                    ->constrained('invoices')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('payments', 'direction')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('direction', ['tahsilat', 'odeme'])->default('tahsilat')->after('payment_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'refunded_invoice_id')) {
                $table->dropForeign(['refunded_invoice_id']);
                $table->dropColumn('refunded_invoice_id');
            }
        });

        if (Schema::hasColumn('payments', 'direction')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('direction');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('invoices', function (Blueprint $table) {
                $table->enum('type', ['satis', 'alis'])->default('satis')->change();
            });
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->enum('type', ['acilis', 'alis', 'satis', 'duzeltme'])->change();
            });
        }
    }
};
