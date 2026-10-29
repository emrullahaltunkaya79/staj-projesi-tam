<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->enum('type', ['percent', 'fixed', 'free_shipping'])->default('percent');
            $table->decimal('value', 10, 2)->default(0); // percent: 10 = %10, fixed: 50 = 50₺
            $table->decimal('min_order_total', 10, 2)->default(0);
            $table->integer('usage_limit')->nullable(); // null = sınırsız
            $table->integer('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
