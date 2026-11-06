<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Bir ürünün varyantları tek boyutludur (renk ya da beden): sipariş satırı tek variant_id taşır
            $table->string('type', 10)->default('renk')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
