<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kombi (renk + beden) varyant desteği: iki öznitelik sütunu
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('renk', 50)->nullable()->after('name');
            $table->string('beden', 50)->nullable()->after('renk');
        });

        // Eski tek boyutlu varyantlar: ad, kendi özniteliğine taşınır
        DB::table('product_variants')->where('type', 'renk')->update(['renk' => DB::raw('`name`')]);
        DB::table('product_variants')->where('type', 'beden')->update(['beden' => DB::raw('`name`')]);
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['renk', 'beden']);
        });
    }
};
