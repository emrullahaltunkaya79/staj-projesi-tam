<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendStockAlerts extends Command
{
    protected $signature = 'stok:bildirim-gonder';

    protected $description = 'Stoğa geri dönen ürünler için bekleyen bildirim kayıtlarına e-posta gönderir';

    public function handle(): int
    {
        // Bildirimi bekleyen kayıtlar → ürünü stoğa girmiş olanlar
        $kayitlar = DB::table('stock_alerts')
            ->whereNull('notified_at')
            ->join('products', 'products.id', '=', 'stock_alerts.product_id')
            ->select('stock_alerts.id', 'stock_alerts.email', 'stock_alerts.variant_id',
                'stock_alerts.product_id', 'products.name as urun_adi', 'products.stock_quantity')
            ->get();

        if ($kayitlar->isEmpty()) {
            $this->line('Gönderilecek bekleyen stok bildirimi yok.');

            return self::SUCCESS;
        }

        $gonderilen = 0;
        $bekleyen = 0;

        foreach ($kayitlar as $kayit) {
            $urun = Product::find($kayit->product_id);

            // Hâlâ tükendi ise beklet
            if (! $urun || $urun->availableQuantity() <= 0) {
                $bekleyen++;
                continue;
            }

            Mail::raw(
                "İyi haberler! 🎉\n\n".
                "\"{$kayit->urun_adi}\" stoğa geri geldi.\n".
                "HepsiBirarada mağazasından hemen inceleyip sipariş verebilirsiniz:\n".
                "https://magaza.hepsibirarada.test/urun/{$kayit->product_id}\n\n".
                "Bu e-postayı stok bildirimi kaydınız üzerine gönderdik.",
                function ($message) use ($kayit) {
                    $message->to($kayit->email)->subject("{$kayit->urun_adi} stoğa geri geldi!");
                },
            );

            DB::table('stock_alerts')->where('id', $kayit->id)->update(['notified_at' => now()]);
            $gonderilen++;
        }

        $this->info("Stok bildirimi: {$gonderilen} gönderildi, {$bekleyen} beklemeye alındı (stok henüz yok).");

        return self::SUCCESS;
    }
}
