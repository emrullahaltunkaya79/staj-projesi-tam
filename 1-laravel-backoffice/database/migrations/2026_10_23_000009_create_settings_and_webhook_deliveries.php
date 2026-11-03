<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // key-value ayar deposu — .env yerine panel'den yönetilebilen ayarlar
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event', 50);
            $table->string('url', 500);
            $table->json('payload');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('delivered_at');
            $table->timestamps();

            $table->index(['success', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('settings');
    }
};
