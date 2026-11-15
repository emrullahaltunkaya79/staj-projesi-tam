<?php

namespace App\Console\Commands;

use App\Mail\BirthdayCouponMail;
use App\Models\Coupon;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Doğum günü kuponu: bugün doğum günü olan müşterilere %15 kupon kodu e-posta ile gider.
 * Kod yıllara göre benzersizdir (DOGUM-2026-{cari_id}) → her yıl bir kez.
 */
class GonderDogumGunuKuponlari extends Command
{
    protected $signature = 'gonder:dogum-gunu-kuponlari';

    protected $description = 'Doğum günü olan müşterilere %15 hediye kuponu oluşturur ve e-postalar';

    public function handle(): int
    {
        $bugun = now();
        $cariler = Contact::query()
            ->whereNotNull('dogum_tarihi')
            ->whereMonth('dogum_tarihi', $bugun->month)
            ->whereDay('dogum_tarihi', $bugun->day)
            ->get();

        $verilen = 0;

        foreach ($cariler as $cari) {
            $kod = 'DOGUM-' . $bugun->format('Y') . '-' . $cari->id;

            if (Coupon::where('code', $kod)->exists()) {
                continue; // bu yılın kuponu zaten verilmiş
            }

            Coupon::create([
                'company_id' => $cari->company_id,
                'code' => $kod,
                'name' => 'Doğum günü kuponu (%15)',
                'type' => 'percent',
                'value' => 15,
                'min_order_total' => 0,
                'usage_limit' => 1,
                'expires_at' => $bugun->copy()->addDays(14)->endOfDay(),
                'is_active' => true,
            ]);

            $kullanici = User::where('email', $cari->email)->whereNull('deleted_at')->first();
            if ($kullanici && $kullanici->email_verified_at) {
                Mail::to($kullanici->email)->send(
                    new BirthdayCouponMail($cari->name, $kod, $bugun->copy()->addDays(14)->locale('tr')->translatedFormat('d F Y'))
                );
            }

            $verilen++;
        }

        $this->info("{$verilen} doğum günü kuponu oluşturuldu.");

        return self::SUCCESS;
    }
}
