<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Terk edilmiş sepet: son hatırlatma damgası (günde bir)
        Schema::table('customer_states', function (Blueprint $table) {
            $table->timestamp('sepet_hatirlatma')->nullable()->after('sepet');
        });

        // Müşteri: doğum günü kuponu + referans programı + Plus üyelik
        Schema::table('contacts', function (Blueprint $table) {
            $table->date('dogum_tarihi')->nullable()->after('email');
            $table->string('referans_kodu', 20)->nullable()->unique()->after('dogum_tarihi');
            $table->boolean('referans_kazandi')->default(false)->after('referans_kodu');
            $table->timestamp('plus_bitis')->nullable()->after('referans_kazandi');
        });

        // Ön sipariş: tükendi üründe satıcı açarsa müşteri önceden sipariş verebilir
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('pre_order')->default(false)->after('video_url');
        });

        // 2FA: giriş sırasında ikinci faktör (e-posta kodu)
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('iki_adim')->default(false)->after('role');
            $table->string('iki_adim_kodu')->nullable()->after('iki_adim');
            $table->timestamp('iki_adim_son')->nullable()->after('iki_adim_kodu');
        });

        // Paket kampanyaları: "3 al 2 öde" ve "Eşik üstü yarı fiyat"
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('ad', 100);
            $table->enum('tur', ['x_al_y_ode', 'esik_yarim']);
            $table->foreignId('urun_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedSmallInteger('al_adet')->default(3);
            $table->unsignedSmallInteger('ode_adet')->default(2);
            $table->decimal('esik', 12, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['iki_adim', 'iki_adim_kodu', 'iki_adim_son']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('pre_order');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['dogum_tarihi', 'referans_kodu', 'referans_kazandi', 'plus_bitis']);
        });
        Schema::table('customer_states', function (Blueprint $table) {
            $table->dropColumn('sepet_hatirlatma');
        });
    }
};
