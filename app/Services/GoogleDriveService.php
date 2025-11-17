<?php

namespace App\Services;

use App\Models\User;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class GoogleDriveService
{
    protected Google_Client $client;
    protected ?Google_Service_Drive $drive = null;
    protected string $folderId;
    protected ?User $user = null;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? auth()->user();
        
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->setScopes([
            'https://www.googleapis.com/auth/drive.file',
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $this->folderId = config('services.google.drive_folder_id', '');

        // Load token from user if available
        if ($this->user && $this->user->google_drive_token) {
            try {
                $accessToken = json_decode(Crypt::decryptString($this->user->google_drive_token), true);
                $this->client->setAccessToken($accessToken);

                // Refresh token if expired
                if ($this->client->isAccessTokenExpired()) {
                    if ($this->client->getRefreshToken()) {
                        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                        $this->saveToken();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to decrypt Google Drive token', ['error' => $e->getMessage()]);
            }
        } else {
            // Fallback: Try old shared token file (for backward compatibility)
            $tokenPath = storage_path('app/google-drive-token.json');
            if (file_exists($tokenPath)) {
                $accessToken = json_decode(file_get_contents($tokenPath), true);
                $this->client->setAccessToken($accessToken);

                // Refresh token if expired
                if ($this->client->isAccessTokenExpired()) {
                    if ($this->client->getRefreshToken()) {
                        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                        $this->saveToken();
                    }
                }
            }
        }

        // Initialize drive service only if authenticated
        if ($this->isAuthenticated()) {
            $this->drive = new Google_Service_Drive($this->client);
        }
    }

    /**
     * Get authorization URL
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback
     */
    public function handleCallback(string $code, ?User $user = null): bool
    {
        $user = $user ?? $this->user ?? auth()->user();
        
        if (!$user) {
            Log::error('Google Drive callback: No user found');
            return false;
        }

        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            $this->client->setAccessToken($accessToken);

            if (array_key_exists('error', $accessToken)) {
                Log::error('Google Drive auth error', ['error' => $accessToken['error']]);
                return false;
            }

            $this->user = $user;
            $this->saveToken();
            
            // Update user's connection timestamp
            $user->update([
                'google_drive_connected_at' => now(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive callback error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Save access token
     */
    protected function saveToken(): void
    {
        $token = $this->client->getAccessToken();
        
        if ($this->user) {
            // Save encrypted token to user record
            $this->user->update([
                'google_drive_token' => Crypt::encryptString(json_encode($token)),
                'google_drive_connected_at' => now(),
            ]);
        } else {
            // Fallback: Save to file (for backward compatibility)
            $tokenPath = storage_path('app/google-drive-token.json');
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Check if authenticated
     */
    public function isAuthenticated(): bool
    {
        // Check user's token first
        if ($this->user && $this->user->google_drive_token) {
            try {
                $accessToken = json_decode(Crypt::decryptString($this->user->google_drive_token), true);
                $this->client->setAccessToken($accessToken);

                if ($this->client->isAccessTokenExpired()) {
                    if ($this->client->getRefreshToken()) {
                        try {
                            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                            $this->saveToken();
                            return true;
                        } catch (\Exception $e) {
                            Log::error('Failed to refresh Google Drive token', ['error' => $e->getMessage()]);
                            return false;
                        }
                    }
                    return false;
                }

                return true;
            } catch (\Exception $e) {
                Log::error('Failed to decrypt Google Drive token', ['error' => $e->getMessage()]);
            }
        }

        // Fallback: Check old shared token file
        $tokenPath = storage_path('app/google-drive-token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);

            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    try {
                        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                        $this->saveToken();
                        return true;
                    } catch (\Exception $e) {
                        Log::error('Failed to refresh Google Drive token', ['error' => $e->getMessage()]);
                        return false;
                    }
                }
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Upload file to Google Drive
     */
    public function uploadFile(string $localPath, string $fileName, ?string $parentFolderId = null): ?string
    {
        if (!$this->isAuthenticated() || !$this->drive) {
            Log::error('Google Drive not authenticated');
            return null;
        }

        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $fileName,
                'parents' => [$parentFolderId ?? $this->folderId],
            ]);

            $content = file_get_contents($localPath);
            $mimeType = mime_content_type($localPath) ?: 'application/octet-stream';

            $file = $this->drive->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);

            return $file->getId();
        } catch (\Exception $e) {
            Log::error('Google Drive upload failed', [
                'error' => $e->getMessage(),
                'file' => $fileName,
            ]);
            return null;
        }
    }

    /**
     * Download file from Google Drive
     */
    public function downloadFile(string $fileId): ?string
    {
        if (!$this->isAuthenticated() || !$this->drive) {
            Log::error('Google Drive not authenticated');
            return null;
        }

        try {
            $response = $this->drive->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            $tempPath = storage_path('app/temp/' . uniqid() . '_' . $fileId);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $content);
            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Google Drive download failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
            ]);
            return null;
        }
    }

    /**
     * Delete file from Google Drive
     */
    public function deleteFile(string $fileId): bool
    {
        if (!$this->isAuthenticated() || !$this->drive) {
            Log::error('Google Drive not authenticated');
            return false;
        }

        try {
            $this->drive->files->delete($fileId);
            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive delete failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
            ]);
            return false;
        }
    }

    /**
     * Get file metadata
     */
    public function getFileMetadata(string $fileId): ?array
    {
        if (!$this->isAuthenticated() || !$this->drive) {
            return null;
        }

        try {
            $file = $this->drive->files->get($fileId, ['fields' => 'id,name,size,mimeType,createdTime,modifiedTime']);
            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'created_at' => $file->getCreatedTime(),
                'updated_at' => $file->getModifiedTime(),
            ];
        } catch (\Exception $e) {
            Log::error('Google Drive get metadata failed', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
            ]);
            return null;
        }
    }

    /**
     * Create folder
     */
    public function createFolder(string $folderName, ?string $parentFolderId = null): ?string
    {
        if (!$this->isAuthenticated() || !$this->drive) {
            return null;
        }

        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId ?? $this->folderId],
            ]);

            $file = $this->drive->files->create($fileMetadata, ['fields' => 'id']);
            return $file->getId();
        } catch (\Exception $e) {
            Log::error('Google Drive create folder failed', [
                'error' => $e->getMessage(),
                'folder_name' => $folderName,
            ]);
            return null;
        }
    }

    /**
     * Get or create folder for organization
     */
    public function getOrCreateOrganizationFolder(string $organizationId, string $organizationName): ?string
    {
        if (!$this->isAuthenticated() || !$this->drive) {
            return null;
        }

        // Cache folder IDs to reduce API calls
        $cacheKey = "google_drive_folder_{$organizationId}";
        $cachedFolderId = cache()->get($cacheKey);

        if ($cachedFolderId) {
            return $cachedFolderId;
        }

        // Try to find existing folder
        try {
            $query = "name='{$organizationName}' and mimeType='application/vnd.google-apps.folder' and '{$this->folderId}' in parents and trashed=false";
            $results = $this->drive->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
            ]);

            if (count($results->getFiles()) > 0) {
                $folderId = $results->getFiles()[0]->getId();
                cache()->put($cacheKey, $folderId, now()->addDays(24));
                return $folderId;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to search for existing folder', ['error' => $e->getMessage()]);
        }

        // Create new folder
        $folderId = $this->createFolder($organizationName);
        if ($folderId) {
            cache()->put($cacheKey, $folderId, now()->addDays(24));
        }

        return $folderId;
    }
}

