<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo verisi: 4 aylık gerçekçi geçmiş ile dolu bir sistem.
     * Detaylar DemoSeeder'da.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
