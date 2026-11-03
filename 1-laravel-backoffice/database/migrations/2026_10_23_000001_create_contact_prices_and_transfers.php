<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cari bazlı özel fiyat listesi
        Schema::create('contact_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 2);
            $table->timestamps();
            $table->unique(['contact_id', 'product_id']);
        });

        // Kasa → Banka virman kayıtları
        Schema::create('cash_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            // nakitten_havaleye veya havaleden_nakite
            $table->enum('direction', ['nakit_havale', 'havale_nakit']);
            $table->string('description')->nullable();
            $table->date('transfer_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transfers');
        Schema::dropIfExists('contact_prices');
    }
};
