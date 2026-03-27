<?php

use App\Console\Commands\MonitorSites;
use App\Models\Scan;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete scans older than 30 days — as stated in the Privacy Policy
Schedule::call(function () {
    Scan::where('created_at', '<', now()->subDays(30))->delete();
})->daily()->name('delete-old-scans');

// Weekly monitoring: scan all user-tracked sites and send alerts
Schedule::command(MonitorSites::class)->weekly()->name('monitor-sites');
