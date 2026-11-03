<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // musteri = müşteri (bize borçlanabilir), tedarikci = tedarikçi (biz borçlanabiliriz)
            $table->enum('type', ['musteri', 'tedarikci'])->default('musteri');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_number')->nullable();
            $table->text('address')->nullable();
            // Bakiye > 0 => cari bize borçlu (alacak), < 0 => biz cariye borçluyuz
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
