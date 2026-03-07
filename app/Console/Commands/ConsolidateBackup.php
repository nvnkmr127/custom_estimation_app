<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Models\Setting;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * backup:consolidate
 *
 * End-of-day process:
 *  - Sunday: Run FULL backup (Source Code + DB + All Storage)
 *  - Mon-Sat: Run INCREMENTAL backup (DB + Only New/Changed Storage)
 *  - Prunes data older than 3 months everywhere.
 */
class ConsolidateBackup extends Command
{
    protected $signature = 'backup:consolidate';
    protected $description = 'Daily EOD: DB/Incremental Storage. Sundays: Full Code. 3-month cleanup.';

    public function __construct(
        protected BackupService $backupService,
        protected GoogleDriveBackupService $googleDrive
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');

        $enabled = Setting::where('key', 'backup_enabled')->value('value') ?? '0';
        if ($enabled !== '1')
            return self::SUCCESS;

        $isSunday = now()->isSunday();

        // Strategy Selection
        if ($isSunday) {
            $this->info("🔄 Sunday: Starting FULL system backup (Code + DB + All Storage)…");
            $method = 'runFullBackup';
        } else {
            $this->info("🔄 Mon-Sat: Starting Incremental backup (DB + Changed Storage)…");
            $method = 'runIncrementalBackup';
        }

        try {
            $log = $this->backupService->$method('consolidate');
            $this->info("  ✅ Success: {$log->filename} ({$log->formatted_size})");
        } catch (\Exception $e) {
            $this->error('  ❌ Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Upload to Drive
        if ($this->googleDrive->isConfigured() && $this->googleDrive->isEnabled()) {
            $this->info('  [Drive] Uploading result…');
            try {
                $log->update(['status' => 'uploading_drive']);
                $result = $this->googleDrive->upload($log);
                $log->update([
                    'status' => 'drive_uploaded',
                    'google_drive_id' => $result['id'],
                    'google_drive_url' => $result['url'],
                ]);
                $this->info("  ✅ Cloud URL: {$result['url']}");
            } catch (\Exception $e) {
                $this->warn('  ⚠ Drive upload skipped: ' . $e->getMessage());
            }
        }

        // Cleanup
        $this->info('  [Retention] Purging data older than 90 days…');
        $this->backupService->deleteCheckpoints();
        $prunedLocal = $this->backupService->pruneOldBackups();

        if ($this->googleDrive->isEnabled()) {
            $prunedCloud = $this->googleDrive->rotate();
            $this->info("  ✅ Cleanup: {$prunedLocal} local skipped, {$prunedCloud} cloud purged.");
        }

        $notifyEmail = Setting::where('key', 'backup_notify_email')->value('value');
        if ($notifyEmail)
            $this->sendNotification($notifyEmail, $log, $isSunday);

        // 6. External Heartbeat Ping
        $heartbeatUrl = Setting::where('key', 'backup_heartbeat_url')->value('value');
        if ($heartbeatUrl) {
            try {
                \Illuminate\Support\Facades\Http::get($heartbeatUrl);
                $this->info('  [Heartbeat] Ping sent successfully.');
            } catch (\Exception $e) {
                Log::warning('Heartbeat ping failed: ' . $e->getMessage());
            }
        }

        $this->info('🎉 Process complete.');
        return self::SUCCESS;
    }

    protected function sendNotification(string $email, BackupLog $log, bool $isFull): void
    {
        try {
            $appName = Setting::where('key', 'app_name')->value('value') ?? config('app.name');
            $subject = $isFull ? "[{$appName}] Weekly FULL Recovery Point Created" : "[{$appName}] Daily Backup Success";
            $body = "A recovery point has been created.\nFile: {$log->filename}\nSize: {$log->formatted_size}\nType: " . ($isFull ? 'Full System' : 'Database + Incremental Storage');
            Mail::raw($body, fn($m) => $m->to($email)->subject($subject));
        } catch (\Exception $e) {
            Log::warning('Notify failed: ' . $e->getMessage());
        }
    }
}
