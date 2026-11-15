<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Katalogda gizli ürünler (üyelik paketleri gibi) — mağaza vitrininde listelenmez. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('katalog_gizli')->default(false)->after('pre_order');
        });

        // Plus üyelik ürünü yalnızca Hesabım > Plus kartından satılır
        \Illuminate\Support\Facades\DB::table('products')
            ->where('code', 'PLUS-1YIL')
            ->update(['katalog_gizli' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('katalog_gizli');
        });
    }
};
