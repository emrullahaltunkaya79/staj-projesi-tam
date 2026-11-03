<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denetim izi (activity log) ve geri alınabilir silme (soft delete):
 *  - activity_logs: kim, neyi, ne zaman oluşturdu/değiştirdi/sildi
 *  - deleted_at: silinen kayıtlar kalıcı olarak yok edilmez, geri yüklenebilir
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 20); // created / updated / deleted / restored
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('description');
            $table->json('changes')->nullable(); // değişen alanlar (updated için)
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });

        foreach (['contacts', 'products', 'expenses', 'quotes', 'users'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        foreach (['contacts', 'products', 'expenses', 'quotes', 'users'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
