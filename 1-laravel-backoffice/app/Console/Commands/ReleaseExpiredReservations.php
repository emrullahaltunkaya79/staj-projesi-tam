<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    protected $signature = 'siparisler:rezerv-serbest-birak';

    protected $description = 'Ödemesi 30 dakika içinde gelmeyen rezerve siparişleri serbest bırakır';

    public function handle(OrderService $orderService): int
    {
        $total = 0;

        foreach (Company::pluck('id') as $companyId) {
            $released = $orderService->releaseExpiredReservations($companyId);
            $total += $released;

            if ($released > 0) {
                $this->info("Şirket #{$companyId}: {$released} rezervasyon serbest bırakıldı.");
            }
        }

        $this->info("Tamamlandı: {$total} rezervasyon serbest bırakıldı.");

        return self::SUCCESS;
    }
}
