<?php

namespace App\Services;

use App\Models\BackupLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    protected string $disk = 'local';
    protected string $backupDir = 'backups';

    /**
     * Run a FULL backup: database + storage + CODE.
     * Normally run weekly.
     */
    public function runFullBackup(string $triggeredBy = 'system'): BackupLog
    {
        return $this->createBackup($triggeredBy, BackupLog::TYPE_FINAL, includeStorage: true, includeCode: true, incremental: false);
    }

    /**
     * Run a STANDARD backup: database + storage files.
     * Runs daily.
     */
    public function runBackup(string $triggeredBy = 'system'): BackupLog
    {
        return $this->createBackup($triggeredBy, BackupLog::TYPE_FINAL, includeStorage: true, includeCode: false, incremental: false);
    }

    /**
     * Run an INCREMENTAL backup: database + only changed storage files.
     */
    public function runIncrementalBackup(string $triggeredBy = 'system'): BackupLog
    {
        return $this->createBackup($triggeredBy, BackupLog::TYPE_FINAL, includeStorage: true, includeCode: false, incremental: true);
    }

    /**
     * Run a CHECKPOINT backup: database only (fast, small).
     */
    public function runCheckpoint(string $triggeredBy = 'schedule'): BackupLog
    {
        return $this->createBackup($triggeredBy, BackupLog::TYPE_CHECKPOINT, includeStorage: false, includeCode: false, incremental: false);
    }

    /**
     * Core backup builder.
     */
    protected function createBackup(
        string $triggeredBy,
        string $backupType,
        bool $includeStorage,
        bool $includeCode,
        bool $incremental
    ): BackupLog {
        $startedAt = now();
        $prefix = $includeCode ? 'full' : ($incremental ? 'inc' : ($backupType === BackupLog::TYPE_CHECKPOINT ? 'checkpoint' : 'backup'));
        $filename = $prefix . '_' . $startedAt->format('Y-m-d_His') . '_' . Str::random(6) . '.zip';
        $relativePath = $this->backupDir . '/' . $filename;

        Log::info("[BackupService] Starting {$backupType} " . ($includeCode ? 'full ' : '') . ($incremental ? 'incremental ' : '') . "backup: {$filename}");

        Storage::disk($this->disk)->makeDirectory($this->backupDir);
        $absolutePath = storage_path('app/private/' . $relativePath);

        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create ZIP archive at: {$absolutePath}");
        }

        // 1️⃣ Zero-Downtime Database Backup
        $this->addDatabaseToZip($zip);

        $manifest = [];
        $parentId = null;

        // 2️⃣ Storage Backup
        if ($includeStorage) {
            if ($incremental) {
                $lastBackup = BackupLog::finals()->completed()->orderBy('created_at', 'desc')->first();
                $lastManifest = $lastBackup?->manifest ?? [];
                $parentId = $lastBackup?->id;
                $manifest = $this->addIncrementalStorageToZip($zip, $lastManifest);
            } else {
                $manifest = $this->addStorageToZip($zip);
            }
        }

        // 3️⃣ Code Backup (Recursive, avoiding binary bloat)
        if ($includeCode) {
            $this->addCodeToZip($zip);
        }

        $zip->close();
        $sizeBytes = filesize($absolutePath);
        $fileHash = hash_file('sha256', $absolutePath);

        Log::info("[BackupService] Backup complete: {$filename} ({$sizeBytes} bytes)");

        return BackupLog::create([
            'filename' => $filename,
            'path' => $relativePath,
            'size_bytes' => $sizeBytes,
            'disk' => $this->disk,
            'status' => 'completed',
            'triggered_by' => $triggeredBy,
            'backup_type' => $backupType,
            'is_incremental' => $incremental,
            'parent_id' => $parentId,
            'manifest' => $manifest,
            'hash' => $fileHash,
            'started_at' => $startedAt,
            'completed_at' => now(),
        ]);
    }

    /**
     * Zero-Downtime Database Backup.
     */
    protected function addDatabaseToZip(ZipArchive $zip): void
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (file_exists($dbPath)) {
                $tempDb = sys_get_temp_dir() . '/sqlite_backup_' . time() . '.sqlite';
                copy($dbPath, $tempDb);
                $zip->addFile($tempDb, 'database/database.sqlite');
                Log::info("[BackupService] Added Zero-Downtime SQLite snapshot.");
                register_shutdown_function(fn() => @unlink($tempDb));
            }
        } elseif (in_array($connection, ['mysql', 'mariadb'])) {
            $this->addMysqlDumpToZip($zip);
        }
    }

    protected function addMysqlDumpToZip(ZipArchive $zip): void
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $tempFile = sys_get_temp_dir() . '/db_dump_' . time() . '.sql';
        $cnfFile = sys_get_temp_dir() . '/my_cnf_' . time() . '.cnf';

        // Use a temporary option file to hide password from 'ps aux'
        $cnfContent = "[client]\npassword=\"" . addcslashes($password, '"\\') . "\"\n";
        file_put_contents($cnfFile, $cnfContent);
        chmod($cnfFile, 0600);

        $cmd = sprintf(
            'mysqldump --defaults-extra-file=%s --single-transaction --quick --lock-tables=false -h %s -P %d -u %s %s > %s 2>&1',
            escapeshellarg($cnfFile),
            escapeshellarg($host),
            (int) $port,
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($tempFile)
        );

        exec($cmd, $output, $exitCode);
        @unlink($cnfFile);

        if ($exitCode === 0 && file_exists($tempFile)) {
            $zip->addFile($tempFile, 'database/database.sql');
            register_shutdown_function(fn() => @unlink($tempFile));
        } else {
            Log::error("[BackupService] mysqldump failed: " . implode("\n", $output));
        }
    }

    /**
     * Full Storage Backup.
     */
    protected function addStorageToZip(ZipArchive $zip): array
    {
        $storagePath = storage_path('app/public');
        if (!is_dir($storagePath))
            return [];

        $manifest = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                $relativePath = ltrim(str_replace($storagePath, '', $realPath), DIRECTORY_SEPARATOR);
                $hash = hash_file('md5', $realPath);

                $zip->addFile($realPath, 'storage/' . $relativePath);
                $manifest[$relativePath] = $hash;
            }
        }
        return $manifest;
    }

    /**
     * Incremental Storage Backup.
     */
    protected function addIncrementalStorageToZip(ZipArchive $zip, array $lastManifest): array
    {
        $storagePath = storage_path('app/public');
        if (!is_dir($storagePath))
            return [];

        $currentManifest = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storagePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $addedCount = 0;
        foreach ($files as $file) {
            if ($file->isFile()) {
                $realPath = $file->getRealPath();
                $relativePath = ltrim(str_replace($storagePath, '', $realPath), DIRECTORY_SEPARATOR);
                $hash = hash_file('md5', $realPath);

                $currentManifest[$relativePath] = $hash;

                if (!isset($lastManifest[$relativePath]) || $lastManifest[$relativePath] !== $hash) {
                    $zip->addFile($realPath, 'storage/' . $relativePath);
                    $addedCount++;
                }
            }
        }

        Log::info("[BackupService] Incremental storage: added {$addedCount} files.");
        return $currentManifest;
    }

    /**
     * Add source code to the zip, excluding bloat.
     */
    protected function addCodeToZip(ZipArchive $zip): void
    {
        $rootPath = base_path();
        $exclude = [
            'vendor',
            'node_modules',
            '.git',
            'storage', // Handled by addStorageToZip or skipped
            '.gemini',
            '.next',
            'dist',
            'public/storage'
        ];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $addedCount = 0;
        foreach ($files as $file) {
            $realPath = $file->getRealPath();
            $relativePath = ltrim(str_replace($rootPath, '', $realPath), DIRECTORY_SEPARATOR);

            // Skip excluded dirs
            foreach ($exclude as $ex) {
                if (str_starts_with($relativePath, $ex))
                    continue 2;
            }

            if ($file->isFile()) {
                $zip->addFile($realPath, 'code/' . $relativePath);
                $addedCount++;
            }
        }
        Log::info("[BackupService] Added {$addedCount} source code files.");
    }

    /**
     * Restore Backup.
     */
    public function restore(BackupLog $log): void
    {
        Log::info("[BackupService] Starting restoration: {$log->filename}");
        $log->update(['status' => 'restoring']);

        $absolutePath = storage_path('app/private/' . $log->path);
        if (!file_exists($absolutePath))
            throw new \RuntimeException("File missing.");

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true)
            throw new \RuntimeException("Zip error.");

        $tempExtract = storage_path('app/private/temp_restore_' . Str::random(8));
        mkdir($tempExtract, 0755, true);

        try {
            $zip->extractTo($tempExtract);
            $zip->close();

            $this->restoreDatabase($tempExtract);
            $this->restoreStorage($tempExtract, $log);
            // We usually don't auto-restore code to avoid breaking the running app, 
            // but the files are in the zip for manual recovery.

            Log::info("[BackupService] Restore successful.");
        } catch (\Throwable $e) {
            Log::error("[BackupService] Restore failed", ['exception' => $e]);
            throw $e;
        } finally {
            $this->recursiveDelete($tempExtract);
        }
    }

    protected function restoreDatabase(string $extractPath): void
    {
        $connection = config('database.default');
        if ($connection === 'sqlite') {
            $backupDb = $extractPath . '/database/database.sqlite';
            if (file_exists($backupDb)) {
                $currentDb = config('database.connections.sqlite.database');
                $tempSwap = $currentDb . '.temp_restore';
                
                // Atomic Swap Strategy: Copy to temp, then rename
                // This prevents leaving the app with NO database if the copy is interrupted
                if (!copy($backupDb, $tempSwap)) {
                    throw new \RuntimeException("Critical: Database restore copy failed.");
                }
                
                // Perform the swap
                if (file_exists($currentDb)) {
                    rename($currentDb, $currentDb . '.bak_' . time());
                }
                rename($tempSwap, $currentDb);
                
                Log::info("[BackupService] SQLite database swapped atomically.");
            }
        }
    }

    protected function restoreStorage(string $extractPath, BackupLog $log): void
    {
        $storagePath = storage_path('app/public');
        $extractedStorage = $extractPath . '/storage';

        if (is_dir($extractedStorage)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractedStorage, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($extractedStorage, '', $file->getRealPath());
                    $destPath = $storagePath . $relativePath;
                    $destDir = dirname($destPath);
                    if (!is_dir($destDir))
                        mkdir($destDir, 0755, true);
                    copy($file->getRealPath(), $destPath);
                }
            }
        }
    }

    public function deleteLocal(BackupLog $log): void
    {
        if (Storage::disk($this->disk)->exists($log->path)) {
            Storage::disk($this->disk)->delete($log->path);
        }
    }

    public function deleteCheckpoints(?string $date = null): int
    {
        $query = BackupLog::checkpoints()->whereDate('created_at', $date ?? today());
        $checkpoints = $query->get();
        $deleted = 0;
        foreach ($checkpoints as $log) {
            $this->deleteLocal($log);
            $log->delete();
            $deleted++;
        }
        return $deleted;
    }

    /**
     * Prune old backups older than 3 months.
     */
    public function pruneOldBackups(int $keep = 10): int
    {
        // Delete backups older than 90 days (3 months)
        $toDelete = BackupLog::where('created_at', '<', now()->subDays(90))->get();

        $deleted = 0;
        foreach ($toDelete as $log) {
            try {
                $this->deleteLocal($log);
                $log->delete();
                $deleted++;
            } catch (\Throwable $e) {
                Log::warning("[BackupService] Delete failed for {$log->filename}", ['exception' => $e]);
            }
        }

        return $deleted;
    }

    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir))
            return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
