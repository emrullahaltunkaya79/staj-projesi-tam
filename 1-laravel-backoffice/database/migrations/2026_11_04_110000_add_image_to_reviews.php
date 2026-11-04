<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function ($table) {
            // Yorum fotoğrafı: storage/app/public/reviews altında saklanır
            $table->string('image_path')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function ($table) {
            $table->dropColumn('image_path');
        });
    }
};
