<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Mağazadan gelen "en yararlı" oyları proxy sabit backoffice token'ıyla
// geldiği için her misafir tek kullanıcıya (admin) bağlanıyordu; ikinci
// ziyaretçinin oyu ilkininkini kaldırıyordu. Oylara tarayıcı kimliği
// (misafir_id) eklenir; yorumlar tablosunda user_id zaten nullable olduğundan
// orada değişiklik gerekmez. Şema kurucusuyla yazıldı — MySQL ve SQLite uyumlu.
return new class extends Migration
{
    public function up(): void
    {
        // MySQL'de review_id FK'sı bu benzersiz indekse yaslanır — önce düz indeks ekle
        Schema::table('review_votes', function (Blueprint $table) {
            $table->index('review_id');
        });

        Schema::table('review_votes', function (Blueprint $table) {
            $table->dropUnique('review_votes_review_id_user_id_unique');
        });

        Schema::table('review_votes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('misafir_id', 64)->nullable()->after('user_id');
            $table->unique(['review_id', 'misafir_id'], 'review_votes_review_misafir_unique');
        });
    }

    public function down(): void
    {
        Schema::table('review_votes', function (Blueprint $table) {
            $table->dropUnique('review_votes_review_misafir_unique');
            $table->dropColumn('misafir_id');
        });

        // Misafir oyları geri dönüşte silinir — user_id zorunlu alana döner
        DB::table('review_votes')->whereNull('user_id')->delete();

        Schema::table('review_votes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
            $table->unique(['review_id', 'user_id']);
            $table->dropIndex(['review_id']);
        });
    }
};
