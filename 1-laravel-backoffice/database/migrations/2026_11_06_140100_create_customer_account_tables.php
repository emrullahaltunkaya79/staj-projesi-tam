<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('baslik', 50);
            $table->string('ad_soyad');
            $table->string('telefon', 30);
            $table->string('il', 50);
            $table->string('ilce', 80);
            $table->string('acik_adres', 500);
            $table->timestamps();
        });

        // Favoriler ve sepet: hesaba bağlı, cihazlar arası taşınır
        Schema::create('customer_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('favoriler')->nullable();
            $table->json('sepet')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_states');
        Schema::dropIfExists('customer_addresses');
    }
};
