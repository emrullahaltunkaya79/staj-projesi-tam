<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pazar yeri komisyon/net kâr simülatörünün kalıcı girdileri:
// pazaryeri               → ürünün planlandığı satış kanalı (web/trendyol/hepsiburada/amazon)
// pazaryeri_komisyon_orani→ ilgili pazar yerinin komisyon yüzdesi (özel oran girilebilir)
// kargo_maliyeti          → satıcıya kalan kargo kesintisi (TL)
// Bu alanlar sipariş entegrasyonlarında varsayılan olarak da kullanılabilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('pazaryeri', 20)->nullable()->after('sale_price');
            $table->decimal('pazaryeri_komisyon_orani', 5, 2)->nullable()->after('pazaryeri');
            $table->decimal('kargo_maliyeti', 10, 2)->nullable()->after('pazaryeri_komisyon_orani');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pazaryeri', 'pazaryeri_komisyon_orani', 'kargo_maliyeti']);
        });
    }
};
