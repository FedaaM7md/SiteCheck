<?php

namespace Database\Seeders;

use App\Services\DemoData;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * In demo mode, seed clearly fictional sample history so lists and reports
     * look alive. With DEMO_MODE=false nothing is seeded.
     */
    public function run(): void
    {
        if (config('sitecheck.demo_mode')) {
            app(DemoData::class)->seed();
        }
    }
}
