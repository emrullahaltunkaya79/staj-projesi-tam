<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casper_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Ödeme için üretilmiş memo — aynı adres kullanılsa bile ödemeyi siparişe bağlar
            $table->string('memo', 64)->unique();
            // CSPR cinsinden istenen tutar
            $table->decimal('amount_cspr', 18, 9);
            $table->decimal('amount_try', 15, 2);
            $table->enum('network', ['testnet', 'mainnet'])->default('testnet');
            $table->enum('status', ['waiting', 'detected', 'confirmed', 'expired'])->default('waiting');
            $table->string('deploy_hash', 100)->nullable(); // blok zincir tx hash'i
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casper_payments');
    }
};
