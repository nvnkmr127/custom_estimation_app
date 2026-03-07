<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('approval:check-timeouts')->hourly();
Schedule::command('estimates:nurture')->daily();
Schedule::command('estimates:check-expiry')->daily();
Schedule::command('automation:run-scheduled')->everyMinute();
Schedule::command('automation:analytics-calculate')->dailyAt('01:00');

// ── Auto Backup Schedule ─────────────────────────────────────────────────────
// Runs hourly; the command checks enabled flag & frequency internally.
Schedule::call(function () {
    $enabled = \App\Models\Setting::where('key', 'backup_enabled')->value('value') ?? '0';
    $frequency = \App\Models\Setting::where('key', 'backup_frequency')->value('value') ?? 'daily';

    if ($enabled !== '1') {
        return; // Auto backup disabled
    }

    // Determine if this hour matches the configured frequency
    $now = now();
    $fire = match ($frequency) {
        'hourly' => true,
        'daily' => $now->hour === 2,                          // 02:XX
        'weekly' => $now->dayOfWeek === 1 && $now->hour === 2, // Mon 02:XX
        'monthly' => $now->day === 1 && $now->hour === 2,       // 1st 02:XX
        default => $now->hour === 2,
    };

    if ($fire) {
        Artisan::call('backup:run', ['--source' => 'schedule']);
    }
})->name('auto-backup')->withoutOverlapping()->hourly();
