<?php

namespace App\Console\Commands;

use App\Mail\AbandonedCartMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Terk edilmiş sepet hatırlatması: sepetinde ürün bekleyen, 2 saatten uzun süredir
 * işlem yapmamış müşterilere günde en fazla bir kez hatırlatma gider.
 */
class GonderSepetHatirlatma extends Command
{
    protected $signature = 'gonder:sepet-hatirlatma';

    protected $description = 'Terk edilmiş sepetteki müşterilere hatırlatma e-postası gönderir (günde bir)';

    public function handle(): int
    {
        $simdi = now();

        $kayitlar = DB::table('customer_states as cs')
            ->join('users as u', 'u.id', '=', 'cs.user_id')
            ->where('u.role', 'musteri')
            ->whereNull('u.deleted_at')
            ->whereNotNull('u.email_verified_at')
            ->where('cs.updated_at', '<=', $simdi->copy()->subHours(2))
            ->where(function ($q) use ($simdi) {
                $q->whereNull('cs.sepet_hatirlatma')
                    ->orWhere('cs.sepet_hatirlatma', '<=', $simdi->copy()->subHours(24));
            })
            ->get(['cs.id', 'cs.sepet', 'u.email', 'u.name']);

        $magazaUrl = rtrim(config('app.magaza_url', env('MAGAZA_URL', 'http://127.0.0.1:3001')), '/');
        $gonderilen = 0;

        foreach ($kayitlar as $kayit) {
            $ogeler = json_decode((string) $kayit->sepet, true) ?: [];
            if (count($ogeler) === 0) {
                continue;
            }

            Mail::to($kayit->email)->send(
                new AbandonedCartMail($kayit->name ?: 'Müşterimiz', $ogeler, $magazaUrl . '/sepet')
            );

            DB::table('customer_states')->where('id', $kayit->id)->update(['sepet_hatirlatma' => $simdi]);
            $gonderilen++;
        }

        $this->info("{$gonderilen} terk edilmiş sepet hatırlatması gönderildi.");

        return self::SUCCESS;
    }
}
