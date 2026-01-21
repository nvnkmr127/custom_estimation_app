<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('approval:check-timeouts')->hourly();
Schedule::command('estimates:nurture')->daily();
Schedule::command('automation:run-scheduled')->everyMinute();
Schedule::command('automation:analytics-calculate')->dailyAt('01:00');
