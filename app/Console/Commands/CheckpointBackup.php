<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * backup:checkpoint
 *
 * Business hours (10am–8pm) incremental backup.
 * Includes DB + only files changed since the last full/incremental backup.
 */
class CheckpointBackup extends Command
{
    protected $signature = 'backup:checkpoint';
    protected $description = 'Create an incremental business-hours checkpoint (DB + changed files)';

    public function __construct(protected BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $enabled = Setting::where('key', 'backup_enabled')->value('value') ?? '0';
        if ($enabled !== '1')
            return self::SUCCESS;

        $hour = (int) now()->format('H');
        if ($hour < 10 || $hour >= 20)
            return self::SUCCESS;

        $this->info('📷 Creating incremental checkpoint…');

        try {
            // Updated to use incremental backup (DB + changed files)
            $log = $this->backupService->runIncrementalBackup('schedule');
            $this->info("✅ Incremental saved: {$log->filename} ({$log->formatted_size})");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Checkpoint failed: ' . $e->getMessage());
            Log::error('[CheckpointBackup] Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
