<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('hediye_paket')->default(false)->after('iade_tutari');
            $table->string('hediye_notu', 500)->nullable()->after('hediye_paket');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['hediye_paket', 'hediye_notu']);
        });
    }
};
