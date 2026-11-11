<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // cspr: online kripto (QR) · kapida: kapıda ödeme (+hizmet bedeli) · havale: banka transferi (%2 indirim)
            $table->enum('payment_method', ['cspr', 'kapida', 'havale'])->default('cspr')->after('status');
        });

        // Eski CSPR ödemeli kayıtlar zaten 'cspr' kabul edilir; tarihsel bekleyenler de cspr kalır.
        DB::table('orders')->whereNull('payment_method')->update(['payment_method' => 'cspr']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
