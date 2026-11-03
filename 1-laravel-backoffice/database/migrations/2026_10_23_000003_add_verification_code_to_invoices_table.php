<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // QR kodun içerdiği kısa doğrulama kodu (fatura başına tekil)
            $table->string('verification_code', 32)->nullable()->unique()->after('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('verification_code');
        });
    }
};
