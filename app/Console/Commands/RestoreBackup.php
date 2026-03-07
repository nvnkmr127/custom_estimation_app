<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * backup:restore {id}
 *
 * Atomically restores a backup.
 * 1. Puts app in maintenance mode (optional, but safer)
 * 2. Swaps DB file (Zero downtime swap)
 * 3. Restores storage files
 * 4. Resets status
 */
class RestoreBackup extends Command
{
    protected $signature = 'backup:restore {id : The ID of the backup log to restore}';
    protected $description = 'Atomically restore an application backup';

    public function __construct(protected BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = $this->argument('id');
        $log = BackupLog::find($id);

        if (!$log) {
            $this->error("Backup log ID {$id} not found.");
            return self::FAILURE;
        }

        if (!$this->confirm("Are you sure you want to restore backup {$log->filename}? The current state will be overwritten.", false)) {
            return self::SUCCESS;
        }

        $this->info("⏳ Restoring backup {$log->filename}...");

        try {
            // Optional: call down command if you want safety
            // $this->call('down', ['--secret' => 'restoring']);

            $this->backupService->restore($log);

            $log->update(['status' => ($log->google_drive_id ? 'drive_uploaded' : 'completed')]);

            // $this->call('up');

            $this->info("✅ Restore complete! The site is now using the state from {$log->created_at->format('Y-m-d H:i')}.");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Restore failed: " . $e->getMessage());
            Log::error("[RestoreBackup] Restoration failed for ID {$id}: " . $e->getMessage());

            $log->update(['status' => 'failed', 'error_message' => 'Restore failed: ' . $e->getMessage()]);
            // $this->call('up');

            return self::FAILURE;
        }
    }
}
