<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendPriceAlerts extends Command
{
    protected $signature = 'fiyat:alarm-gonder';

    protected $description = 'Fiyatı takip fiyatının altına inen ürünler için bekleyen fiyat alarmlarına e-posta gönderir';

    public function handle(): int
    {
        $kayitlar = DB::table('price_alerts')->whereNull('notified_at')->get();

        $gonderilen = 0;

        foreach ($kayitlar as $kayit) {
            $urun = Product::find($kayit->product_id);
            if (! $urun) {
                continue;
            }

            // Takip fiyatı KDV dahil saklanır; karşılaştırma da KDV dahil yapılır
            $guncel = round($urun->finalPrice() * 1.2, 2);
            if ($guncel >= (float) $kayit->takip_fiyati) {
                continue;
            }

            $eski = number_format((float) $kayit->takip_fiyati, 2, ',', '.');
            $yeni = number_format($guncel, 2, ',', '.');

            Mail::raw(
                "Takip ettiğiniz ürünün fiyatı düştü!\n\n".
                "\"{$urun->name}\": {$eski} ₺ → {$yeni} ₺\n".
                "Hemen inceleyin: https://magaza.hepsibirarada.test/urun/{$urun->id}\n\n".
                "Bu e-postayı fiyat alarmı kaydınız üzerine gönderdik.",
                fn ($m) => $m->to($kayit->email)->subject("Fiyat düştü: {$urun->name}"),
            );

            DB::table('price_alerts')->where('id', $kayit->id)->update(['notified_at' => now()]);
            $gonderilen++;
        }

        $this->info("Fiyat alarmı: {$gonderilen} bildirim gönderildi.");

        return self::SUCCESS;
    }
}
