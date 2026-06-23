<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('outbox:publish')->everyTenSeconds();
Schedule::command('stock:expiry-alerts')->dailyAt('06:00');
Schedule::command('consumption:push-to-payroll')->dailyAt('23:30');
