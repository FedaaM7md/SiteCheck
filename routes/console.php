<?php

use App\Services\DemoData;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
 * Copyright (c) 2026 Fedaa Mohamed. All rights reserved.
 */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Demo data reset. SiteCheck has no shared seed rows: history is created only
 * by real scans and scoped to the browser session. The demo command clears
 * that history so a shared demo instance does not accumulate visitors' scans.
 */
Artisan::command('demo:reset', function () {
    $removed = DB::table('scans')->count();
    DB::table('scans')->truncate();

    $seeded = config('sitecheck.demo_mode')
        ? app(DemoData::class)->seed()
        : 0;

    $this->info("Demo data reset: {$removed} scan(s) removed, {$seeded} sample scan(s) seeded.");
})->purpose('Reset demo data (clear history and reseed realistic sample scans)');

if (config('sitecheck.demo_mode')) {
    Schedule::command('demo:reset')->hourly()->withoutOverlapping();
}
