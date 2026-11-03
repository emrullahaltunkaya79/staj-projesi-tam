<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // satis = müşteriye satış, alis = tedarikçiden alış, iade = satış iadesi
            $table->enum('type', ['satis', 'alis', 'iade'])->default('satis')->after('company_id');
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
