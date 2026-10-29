<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            // nakit = nakit tahsilat, havale = banka/havale yoluyla tahsilat
            $table->enum('payment_type', ['nakit', 'havale'])->default('nakit');
            // tahsilat = müşteriden para girişi, odeme = tedarikçiye para çıkışı
            $table->enum('direction', ['tahsilat', 'odeme'])->default('tahsilat');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
