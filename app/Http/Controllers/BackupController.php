<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Models\Setting;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected GoogleDriveBackupService $googleDrive
    ) {
    }

    // -------------------------------------------------------------------------
    // Backup Management Page
    // -------------------------------------------------------------------------

    public function index()
    {
        $logs = BackupLog::orderBy('created_at', 'desc')->paginate(15);
        $settings = Setting::all()->pluck('value', 'key');
        $driveFiles = [];

        if ($this->googleDrive->isConfigured() && $this->googleDrive->isEnabled()) {
            try {
                $driveFiles = $this->googleDrive->listFiles(10);
            } catch (\Exception $e) {
                Log::warning('Could not list Google Drive files: ' . $e->getMessage());
            }
        }

        $keepCount = (int) ($settings->get('backup_keep_count', 10));

        return view('settings.backup', compact('logs', 'settings', 'driveFiles', 'keepCount'));
    }

    // -------------------------------------------------------------------------
    // Manual Backup
    // -------------------------------------------------------------------------

    public function runNow(Request $request)
    {
        try {
            $log = $this->backupService->runBackup('manual');

            // If Drive enabled, upload immediately
            if ($this->googleDrive->isConfigured() && $this->googleDrive->isEnabled()) {
                try {
                    $log->update(['status' => 'uploading_drive']);
                    $result = $this->googleDrive->upload($log);
                    $log->update([
                        'status' => 'drive_uploaded',
                        'google_drive_id' => $result['id'],
                        'google_drive_url' => $result['url'],
                    ]);
                } catch (\Exception $e) {
                    Log::error('Google Drive upload failed after manual backup: ' . $e->getMessage());
                    // Keep status as completed (local backup succeeded)
                    $log->update(['status' => 'completed', 'error_message' => 'Drive: ' . $e->getMessage()]);
                }
            }

            return redirect()->route('backup.index')
                ->with('success', "Backup created: {$log->filename} ({$log->formatted_size})");
        } catch (\Exception $e) {
            Log::error('Manual backup failed: ' . $e->getMessage());
            return redirect()->route('backup.index')
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Download a backup file
    // -------------------------------------------------------------------------

    public function download(BackupLog $backup)
    {
        $localPath = storage_path('app/private/' . $backup->path);

        if (!file_exists($localPath)) {
            return back()->with('error', 'Backup file not found on disk.');
        }

        return response()->download($localPath, $backup->filename);
    }

    // -------------------------------------------------------------------------
    // Upload existing backup to Google Drive
    // -------------------------------------------------------------------------

    public function uploadToDrive(BackupLog $backup)
    {
        if (!$this->googleDrive->isConfigured()) {
            return back()->with('error', 'Google Drive is not configured.');
        }

        try {
            $backup->update(['status' => 'uploading_drive']);
            $result = $this->googleDrive->upload($backup);
            $backup->update([
                'status' => 'drive_uploaded',
                'google_drive_id' => $result['id'],
                'google_drive_url' => $result['url'],
            ]);

            return back()->with('success', 'Uploaded to Google Drive successfully.');
        } catch (\Exception $e) {
            $backup->update(['status' => 'completed']); // revert
            Log::error('Drive upload failed: ' . $e->getMessage());
            return back()->with('error', 'Drive upload failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Restore a backup
    // -------------------------------------------------------------------------

    public function restore(BackupLog $backup)
    {
        try {
            $this->backupService->restore($backup);

            // Revert status from 'restoring' back to whatever it was
            $backup->update(['status' => $backup->google_drive_id ? 'drive_uploaded' : 'completed']);

            return back()->with('success', 'Backup restored successfully!');
        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage());
            $backup->update(['status' => 'failed', 'error_message' => 'Restore failed: ' . $e->getMessage()]);
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Delete a backup
    // -------------------------------------------------------------------------

    public function destroy(BackupLog $backup)
    {
        try {
            $this->backupService->deleteLocal($backup);

            // Also delete from Drive if linked
            if ($backup->google_drive_id && $this->googleDrive->isConfigured()) {
                try {
                    $this->googleDrive->deleteFile($backup->google_drive_id);
                } catch (\Exception $e) {
                    Log::warning('Could not delete Drive file: ' . $e->getMessage());
                }
            }

            $backup->delete();
            return back()->with('success', 'Backup deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Save backup settings
    // -------------------------------------------------------------------------

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'backup_enabled' => 'nullable|boolean',
            'backup_frequency' => 'required|in:hourly,daily,weekly,monthly',
            'backup_keep_count' => 'required|integer|min:1|max:100',
            'backup_google_drive_enabled' => 'nullable|boolean',
            'backup_notify_email' => 'nullable|email|max:255',
            'backup_heartbeat_url' => 'nullable|url|max:255',
        ]);

        $keys = [
            'backup_enabled',
            'backup_frequency',
            'backup_keep_count',
            'backup_google_drive_enabled',
            'backup_notify_email',
            'backup_heartbeat_url',
        ];

        foreach ($keys as $key) {
            $value = $request->input($key);
            if ($key === 'backup_enabled' || $key === 'backup_google_drive_enabled') {
                $value = $request->has($key) ? '1' : '0';
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('backup.index')
            ->with('success', 'Backup settings saved.');
    }

    // -------------------------------------------------------------------------
    // Test Google Drive connection
    // -------------------------------------------------------------------------

    public function testDrive()
    {
        $result = $this->googleDrive->testConnection();
        return response()->json($result);
    }
}
