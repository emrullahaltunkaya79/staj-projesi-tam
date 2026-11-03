<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Ürün silinse bile hareket kaydı isim snapshot'ıyla kalır
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            // acilis = ürün ilk kaydı, alis = alış faturası, satis = satış faturası,
            // iade = satış iadesi (stoğa geri), duzeltme = manuel
            $table->enum('type', ['acilis', 'alis', 'satis', 'iade', 'duzeltme']);
            // giris = stoğa eklendi, cikis = stoktan düştü
            $table->enum('direction', ['giris', 'cikis']);
            $table->decimal('quantity', 15, 2);
            $table->decimal('stock_after', 15, 2);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
