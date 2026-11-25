<?php

use App\Console\Commands\ReleaseExpiredOrders;
use App\Jobs\ReconcileOrganizerStats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

Schedule::command(ReleaseExpiredOrders::class)->everyMinute();
Schedule::job(ReconcileOrganizerStats::class)->dailyAt('02:00');
Schedule::command('sanctum:prune-expired --hours=24')->daily();

//  it will run every minute.
// * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1