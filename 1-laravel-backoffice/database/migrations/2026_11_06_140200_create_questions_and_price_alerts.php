<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('ad', 100);
            $table->string('email', 120);
            $table->text('soru');
            $table->text('cevap')->nullable();
            $table->timestamp('cevaplandi_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'cevaplandi_at']);
        });

        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('email', 120);
            // Kayıt anındaki KDV dahil fiyat; bunun altına inince bildirim gider
            $table->decimal('takip_fiyati', 15, 2);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('product_questions');
    }
};
