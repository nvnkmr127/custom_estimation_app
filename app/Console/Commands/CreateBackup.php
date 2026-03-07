<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CreateBackup extends Command
{
    protected $signature = 'backup:run
                            {--drive : Also upload to Google Drive}
                            {--source=schedule : Who triggered (schedule|manual)}';

    protected $description = 'Create a full application backup (database + storage files)';

    public function __construct(
        protected BackupService $backupService,
        protected GoogleDriveBackupService $googleDrive
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Respect the enabled flag when triggered by schedule
        if ($this->option('source') === 'schedule') {
            $enabled = Setting::where('key', 'backup_enabled')->value('value') ?? '0';
            if ($enabled !== '1') {
                $this->info('Auto backup is disabled. Skipping.');
                return self::SUCCESS;
            }
        }

        $this->info('🗄️  Starting backup…');

        try {
            $log = $this->backupService->runBackup($this->option('source') ?? 'schedule');

            $this->info("✅ Backup complete: {$log->filename} ({$log->formatted_size})");

            // Upload to Google Drive if requested or configured
            $driveEnabled = Setting::where('key', 'backup_google_drive_enabled')->value('value') ?? '0';
            if ($this->option('drive') || $driveEnabled === '1') {
                if ($this->googleDrive->isConfigured()) {
                    $this->info('☁️  Uploading to Google Drive…');
                    try {
                        $log->update(['status' => 'uploading_drive']);
                        $result = $this->googleDrive->upload($log);
                        $log->update([
                            'status' => 'drive_uploaded',
                            'google_drive_id' => $result['id'],
                            'google_drive_url' => $result['url'],
                        ]);
                        $this->info("✅ Uploaded to Google Drive: {$result['url']}");
                    } catch (\Exception $e) {
                        $this->error('Drive upload failed: ' . $e->getMessage());
                        $log->update(['status' => 'completed', 'error_message' => 'Drive: ' . $e->getMessage()]);
                    }
                } else {
                    $this->warn('Google Drive is not configured. Skipping Drive upload.');
                }
            }

            // Pruning
            $keepCount = (int) (Setting::where('key', 'backup_keep_count')->value('value') ?? 10);
            $pruned = $this->backupService->pruneOldBackups($keepCount);
            if ($pruned > 0) {
                $this->info("🗑  Pruned {$pruned} old backup(s). Keeping last {$keepCount}.");
            }

            // Email notification
            $notifyEmail = Setting::where('key', 'backup_notify_email')->value('value');
            if ($notifyEmail) {
                $this->sendNotificationEmail($notifyEmail, $log);
            }

            Log::info("[CreateBackup] Completed. File: {$log->filename}");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('[CreateBackup] Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function sendNotificationEmail(string $email, \App\Models\BackupLog $log): void
    {
        try {
            $appName = Setting::where('key', 'app_name')->value('value') ?? config('app.name');
            $subject = "[{$appName}] Backup Completed: {$log->filename}";
            $body = view('emails.backup-notification', ['log' => $log, 'appName' => $appName])->render();

            Mail::html($body, function ($m) use ($email, $subject) {
                $m->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::warning('[CreateBackup] Email notification failed: ' . $e->getMessage());
        }
    }
}
