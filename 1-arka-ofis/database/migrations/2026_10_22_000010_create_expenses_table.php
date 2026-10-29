<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // kira, elektrik, su, internet, maaş, vergi, diğer...
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->enum('payment_type', ['nakit', 'havale'])->default('nakit');
            $table->timestamps();

            $table->index(['company_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
