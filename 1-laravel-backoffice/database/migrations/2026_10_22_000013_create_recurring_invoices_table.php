<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('title'); // kural adı: "Aylık Kira Faturası"
            // Faturanın kesileceği gün (1-28 — her ayın olduğu günü)
            $table->unsignedTinyInteger('day_of_month');
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->text('notes')->nullable();
            // Kalem şablonu: [{product_id, quantity, unit_price}]
            $table->json('items');
            $table->boolean('is_active')->default(true);
            $table->date('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
