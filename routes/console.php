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

// ── Backup Schedule ───────────────────────────────────────────────────────────
//
//  Business hours strategy:
//    10:00, 13:00, 16:00, 19:00 → checkpoint (DB only, fast, ~2MB)
//    20:00                      → consolidate (full backup + Drive + delete checkpoints)
//
//  The checkpoint command enforces business hours internally (10am-8pm).
//  The consolidate command creates the final keeper and deletes the checkpoints.
//
// ─────────────────────────────────────────────────────────────────────────────

// Checkpoint every 3 hours: 10am, 1pm, 4pm, 7pm
// The command itself also checks backup_enabled and business hours (10–20)
Schedule::command('backup:checkpoint')
    ->cron('0 10,13,16,19 * * *')  // 10:00, 13:00, 16:00, 19:00 every day
    ->withoutOverlapping()
    ->name('backup-checkpoint');

// End-of-day consolidation at 8:00 PM
// Creates full backup → uploads to Drive → deletes today's checkpoints → prunes old finals
Schedule::command('backup:consolidate')
    ->dailyAt('20:00')
    ->withoutOverlapping()
    ->name('backup-consolidate');

// ── Legacy full backup schedule (kept for manual frequency control) ────────────
// Runs when backup_frequency is set to daily/weekly/monthly (not using checkpoint mode)
Schedule::call(function () {
    $enabled = \App\Models\Setting::where('key', 'backup_enabled')->value('value') ?? '0';
    $frequency = \App\Models\Setting::where('key', 'backup_frequency')->value('value') ?? 'daily';

    // Skip if using the new checkpoint+consolidate flow (handled above)
    // This legacy schedule only fires for non-default frequencies
    if ($enabled !== '1' || $frequency === 'daily') {
        return;
    }

    $now = now();
    $fire = match ($frequency) {
        'hourly' => true,
        'weekly' => $now->dayOfWeek === 1 && $now->hour === 2, // Mon 02:XX
        'monthly' => $now->day === 1 && $now->hour === 2,       // 1st 02:XX
        default => false,
    };

    if ($fire) {
        Artisan::call('backup:run', ['--source' => 'schedule']);
    }
})->name('auto-backup-legacy')->withoutOverlapping()->hourly();
