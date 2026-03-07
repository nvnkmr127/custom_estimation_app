<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Google Drive Backup Service
 *
 * Uses Google OAuth2 with a refresh token (service-account style via user OAuth).
 * You must set up a Google Cloud project with Drive API enabled, and store:
 *   GOOGLE_DRIVE_CLIENT_ID
 *   GOOGLE_DRIVE_CLIENT_SECRET
 *   GOOGLE_DRIVE_REFRESH_TOKEN
 *   GOOGLE_DRIVE_FOLDER_ID   (optional — uploads to root if unset)
 *
 * To obtain a refresh token, use Google's OAuth 2.0 Playground:
 * https://developers.google.com/oauthplayground/
 * Scope: https://www.googleapis.com/auth/drive.file
 */
class GoogleDriveBackupService
{
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';
    protected string $uploadUrl = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';
    protected string $filesUrl = 'https://www.googleapis.com/drive/v3/files';

    protected ?string $accessToken = null;

    // -------------------------------------------------------------------------
    // Configuration helpers
    // -------------------------------------------------------------------------

    public function isConfigured(): bool
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        return !empty($clientId) && !empty($clientSecret) && !empty($refreshToken);
    }

    public function isEnabled(): bool
    {
        return (bool) (Setting::where('key', 'backup_google_drive_enabled')->value('value') ?? false);
    }

    // -------------------------------------------------------------------------
    // OAuth Access Token
    // -------------------------------------------------------------------------

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'refresh_token' => config('services.google_drive.refresh_token'),
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to obtain Google Drive access token: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    /**
     * Upload a backup ZIP to Google Drive.
     * Returns the file ID on success.
     */
    public function upload(BackupLog $log): array
    {
        $token = $this->getAccessToken();
        $localPath = storage_path('app/private/' . $log->path);

        if (!file_exists($localPath)) {
            throw new \RuntimeException("Backup file not found: {$localPath}");
        }

        $folderId = config('services.google_drive.folder_id');

        // Build metadata
        $metadata = ['name' => $log->filename, 'mimeType' => 'application/zip'];
        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }

        // Multipart upload
        $response = Http::withToken($token)
            ->attach('metadata', json_encode($metadata), null, ['Content-Type' => 'application/json'])
            ->attach('file', file_get_contents($localPath), $log->filename, ['Content-Type' => 'application/zip'])
            ->post($this->uploadUrl . '&fields=id,webViewLink');

        if ($response->failed()) {
            throw new \RuntimeException('Google Drive upload failed: ' . $response->body());
        }

        return [
            'id' => $response->json('id'),
            'url' => $response->json('webViewLink'),
        ];
    }

    // -------------------------------------------------------------------------
    // List files in backup folder
    // -------------------------------------------------------------------------

    public function listFiles(int $limit = 20): array
    {
        $token = $this->getAccessToken();
        $folderId = config('services.google_drive.folder_id');

        $query = "mimeType='application/zip' and trashed=false";
        if ($folderId) {
            $query .= " and '{$folderId}' in parents";
        }

        $response = Http::withToken($token)
            ->get($this->filesUrl, [
                'q' => $query,
                'orderBy' => 'createdTime desc',
                'pageSize' => $limit,
                'fields' => 'files(id,name,size,createdTime,webViewLink)',
            ]);

        if ($response->failed()) {
            Log::error('[GoogleDriveBackupService] listFiles failed: ' . $response->body());
            return [];
        }

        return $response->json('files', []);
    }

    // -------------------------------------------------------------------------
    // Delete a file from Drive
    // -------------------------------------------------------------------------

    public function deleteFile(string $fileId): bool
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)->delete("{$this->filesUrl}/{$fileId}");

        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // Verify credentials (used by test button)
    // -------------------------------------------------------------------------

    public function testConnection(): array
    {
        try {
            if (!$this->isConfigured()) {
                return ['success' => false, 'message' => 'Google Drive credentials are not configured. Please set GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, and GOOGLE_DRIVE_REFRESH_TOKEN.'];
            }

            $token = $this->getAccessToken();

            // Try a simple files list to verify scopes
            $response = Http::withToken($token)
                ->get($this->filesUrl, ['pageSize' => 1, 'fields' => 'files(id,name)']);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Google Drive connected successfully!'];
            }

            return ['success' => false, 'message' => 'Connection failed: ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
