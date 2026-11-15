<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Canlıya çıkış öncesi demo verilerini temizler.
 * KORUNANLAR: şirketler, panel kullanıcıları (rol != musteri), tedarikçi carileri,
 * depolar, kampanyalar. PLUS üyelik ürünü temizlik sonrası yeniden oluşturulur.
 */
class CanliDemoTemizle extends Command
{
    protected $signature = 'canli:demo-temizle {--force : Onay sormadan çalıştır}';

    protected $description = 'Demo ürün/sipariş/müşteri verilerini temizler (canlıya çıkış hazırlığı)';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('Demo ürünler, siparişler, faturalar, müşteri hesapları ve yorumlar SİLİNECEK. Panel kullanıcıları ve tedarikçi carileri korunur. Devam edilsin mi?')) {
                $this->line('Vazgeçildi.');

                return self::SUCCESS;
            }
        }

        // FK sırasıyla temizleme — her tabloda silinen satır sayısı raporlanır
        $siralar = [
            'recurring_invoice_items' => \Schema::hasTable('recurring_invoice_items') ? DB::table('recurring_invoice_items')->count() : 0,
            'recurring_invoices' => \Schema::hasTable('recurring_invoices') ? DB::table('recurring_invoices')->count() : 0,
            'quote_items' => \Schema::hasTable('quote_items') ? DB::table('quote_items')->count() : 0,
            'quotes' => DB::table('quotes')->count(),
            'payments' => DB::table('payments')->count(), // cari tahsilat/ödeme hareketleri (demo)
            'expenses' => DB::table('expenses')->count(), // demo giderler
            'invoice_items' => DB::table('invoice_items')->count(),
            'invoices' => DB::table('invoices')->count(),
            'orders' => DB::table('orders')->count(),
            'review_votes' => DB::table('review_votes')->count(),
            'product_reviews' => DB::table('product_reviews')->count(),
            'price_history' => DB::table('price_history')->count(),
            'stock_movements' => DB::table('stock_movements')->count(),
            'product_variants' => DB::table('product_variants')->count(),
            'product_images' => DB::table('product_images')->count(),
            'products' => DB::table('products')->count(),
            'coupons' => DB::table('coupons')->count(),
            'contact_messages' => DB::table('contact_messages')->count(),
            'customer_addresses' => DB::table('customer_addresses')->count(),
            'customer_states' => DB::table('customer_states')->count(),
        ];

        foreach ($siralar as $tablo => $adet) {
            if (! \Schema::hasTable($tablo)) continue;
            if ($adet > 0) {
                DB::table($tablo)->delete();
            }
            $this->line("  {$tablo}: {$adet} kayıt temizlendi");
        }

        // Demo müşteri hesapları (panel kullanıcıları korunur)
        $musteriKullanicilar = DB::table('users')->where('role', 'musteri')->delete();
        $this->line("  users (mustteri): {$musteriKullanicilar} hesap temizlendi");

        // Müşteri carileri — tedarikçi/satıcı carileri korunur
        $musteriCariler = DB::table('contacts')->where('type', 'musteri')->delete();
        $this->line("  contacts (mustteri carileri): {$musteriCariler} temizlendi");

        // PLUS üyelik ürününü yeniden oluştur (canlı katalog kurulumuna hazır)
        $sirketId = (int) (DB::table('companies')->min('id') ?: 1);
        DB::table('products')->updateOrInsert(
            ['code' => 'PLUS-1YIL', 'company_id' => $sirketId],
            [
                'name' => 'HepsiBirarada Plus — 1 Yıl Üyelik',
                'unit' => 'adet',
                'purchase_price' => 0,
                'sale_price' => 199,
                'stock_quantity' => 250,
                'katalog_gizli' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        $this->info('PLUS üyelik ürünü yeniden oluşturuldu (stok 250, katalogda gizli).');

        $kalanUrun = DB::table('products')->count();
        $this->info("Kalan ürün sayısı: {$kalanUrun} — gerçek kataloğu panelden girin (Kampanyalar/Mesajlar korunur).");

        return self::SUCCESS;
    }
}
