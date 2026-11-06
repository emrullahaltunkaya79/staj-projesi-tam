<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Sipariş numarası sıralı ve tahmin edilebilir; müşteri erişimi bu anahtarla doğrulanır
            $table->string('access_key', 64)->nullable()->unique()->after('order_no');
            $table->string('customer_phone', 30)->nullable()->after('customer_name');
            $table->string('fatura_tipi', 10)->default('bireysel')->after('shipping_address');
            $table->string('fatura_unvan')->nullable()->after('fatura_tipi');
            $table->string('vergi_dairesi', 100)->nullable()->after('fatura_unvan');
            $table->string('vergi_no', 11)->nullable()->after('vergi_dairesi');
            $table->string('fatura_adresi', 500)->nullable()->after('vergi_no');
            $table->json('iade_talebi')->nullable()->after('notes');
            $table->decimal('iade_tutari', 15, 2)->nullable()->after('iade_talebi');
        });

        DB::table('orders')->whereNull('access_key')->orderBy('id')->each(function ($siparis) {
            DB::table('orders')->where('id', $siparis->id)->update(['access_key' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['access_key']);
            $table->dropColumn([
                'access_key', 'customer_phone', 'fatura_tipi', 'fatura_unvan', 'vergi_dairesi',
                'vergi_no', 'fatura_adresi', 'iade_talebi', 'iade_tutari',
            ]);
        });
    }
};
