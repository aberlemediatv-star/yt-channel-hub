<?php

declare(strict_types=1);

namespace App\Services\CloudImport;

use App\Models\SocialSetting;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use RuntimeException;

final class GdriveCloudService
{
    public function isOAuthConfigured(): bool
    {
        $id = (string) config('cloud_import.gdrive.client_id', '');
        $secret = (string) config('cloud_import.gdrive.client_secret', '');

        return $id !== '' && $secret !== '';
    }

    public function isConnected(): bool
    {
        $t = SocialSetting::getDecrypted('cloud.gdrive.refresh_token', '');

        return is_string($t) && $t !== '';
    }

    public function redirectUri(): string
    {
        return rtrim((string) config('app.url', ''), '/').(string) config('cloud_import.gdrive.redirect_path', '');
    }

    public function buildAuthUrl(string $state): string
    {
        if (! $this->isOAuthConfigured()) {
            throw new RuntimeException('gdrive_oauth_not_configured');
        }
        $client = $this->baseClient();
        $client->setState($state);

        return (string) $client->createAuthUrl();
    }

    public function exchangeCodeForRefreshToken(string $code): void
    {
        $client = $this->baseClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new RuntimeException((string) ($token['error_description'] ?? $token['error']));
        }
        $refresh = (string) ($token['refresh_token'] ?? '');
        if ($refresh === '') {
            throw new RuntimeException('gdrive_no_refresh');
        }
        SocialSetting::setEncrypted('cloud.gdrive.refresh_token', $refresh);
    }

    public function disconnect(): void
    {
        SocialSetting::query()->where('key', 'cloud.gdrive.refresh_token')->delete();
    }

    /**
     * Ordner + Videodateien unter einem Drive-Ordner (`root` = Meine Ablage).
     *
     * @return array{folder_id: string, parent_id: ?string, entries: list<array<string, mixed>>}
     */
    public function browse(string $folderId = 'root'): array
    {
        $folderId = $folderId === '' ? 'root' : $folderId;
        if ($folderId !== 'root' && (strlen($folderId) > 128 || preg_match('/[^-A-Za-z0-9_]/', $folderId))) {
            throw new RuntimeException('gdrive_bad_id');
        }
        $client = $this->authorizedClient();
        $drive = new Drive($client);

        $parentId = null;
        if ($folderId !== 'root') {
            $meta = $drive->files->get($folderId, ['fields' => 'id,parents,mimeType']);
            if ((string) $meta->getMimeType() !== 'application/vnd.google-apps.folder') {
                throw new RuntimeException('gdrive_not_folder');
            }
            $pars = $meta->getParents() ?? [];
            $parentId = isset($pars[0]) ? (string) $pars[0] : 'root';
        }

        $q = "'".$folderId."' in parents and trashed = false";
        $entries = [];
        $pageToken = null;
        $maxPages = 10;
        do {
            $params = [
                'q' => $q,
                'pageSize' => 100,
                'fields' => 'nextPageToken,files(id,name,mimeType,size,modifiedTime)',
                'orderBy' => 'name_natural',
            ];
            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }
            $res = $drive->files->listFiles($params);
            foreach ($res->getFiles() ?? [] as $f) {
                $mime = (string) $f->getMimeType();
                if ($mime === 'application/vnd.google-apps.folder') {
                    $entries[] = [
                        'kind' => 'folder',
                        'id' => (string) $f->getId(),
                        'name' => (string) $f->getName(),
                    ];
                } elseif (str_starts_with($mime, 'video/')) {
                    $entries[] = [
                        'kind' => 'file',
                        'id' => (string) $f->getId(),
                        'name' => (string) $f->getName(),
                        'size' => $f->getSize() !== null ? (string) $f->getSize() : null,
                        'modified' => $f->getModifiedTime(),
                    ];
                }
            }
            $pageToken = $res->getNextPageToken();
            $maxPages--;
        } while ($pageToken !== null && $maxPages > 0);
        usort($entries, static function (array $a, array $b): int {
            $ka = ($a['kind'] ?? '') === 'folder' ? 0 : 1;
            $kb = ($b['kind'] ?? '') === 'folder' ? 0 : 1;
            if ($ka !== $kb) {
                return $ka <=> $kb;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return [
            'folder_id' => $folderId,
            'parent_id' => $parentId,
            'entries' => $entries,
        ];
    }

    /**
     * Download Drive file to a temp path (caller must unlink).
     */
    public function downloadToTempFile(string $fileId): string
    {
        if ($fileId === '' || strlen($fileId) > 128 || preg_match('/[^-A-Za-z0-9_]/', $fileId)) {
            throw new RuntimeException('gdrive_bad_id');
        }
        $client = $this->authorizedClient();
        $drive = new Drive($client);
        $meta = $drive->files->get($fileId, ['fields' => 'id,name,mimeType']);
        $name = (string) $meta->getName();
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'bin';
        }
        $tmp = sys_get_temp_dir().'/'.uniqid('gdrive_', true).'.'.$ext;
        try {
            $http = $client->authorize();
            $response = $http->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId).'?alt=media', [
                'stream' => true,
                'headers' => ['Accept' => '*/*'],
            ]);
            $stream = $response->getBody();
            $fh = fopen($tmp, 'wb');
            if ($fh === false) {
                throw new RuntimeException('gdrive_tmp_open');
            }
            try {
                while (! $stream->eof()) {
                    $chunk = $stream->read(1024 * 1024);
                    if ($chunk === '') {
                        break;
                    }
                    fwrite($fh, $chunk);
                }
            } finally {
                fclose($fh);
            }
            if (! is_readable($tmp) || filesize($tmp) === 0) {
                @unlink($tmp);
                throw new RuntimeException('gdrive_empty');
            }

            return $tmp;
        } catch (\Throwable $e) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }

            throw $e;
        }
    }

    private function baseClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId((string) config('cloud_import.gdrive.client_id'));
        $client->setClientSecret((string) config('cloud_import.gdrive.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes(['https://www.googleapis.com/auth/drive.readonly']);

        return $client;
    }

    private function authorizedClient(): GoogleClient
    {
        $refresh = SocialSetting::getDecrypted('cloud.gdrive.refresh_token', '');
        if (! is_string($refresh) || $refresh === '') {
            throw new RuntimeException('gdrive_not_connected');
        }
        $client = $this->baseClient();
        $client->fetchAccessTokenWithRefreshToken($refresh);
        if ($client->isAccessTokenExpired()) {
            throw new RuntimeException('gdrive_token_expired');
        }

        return $client;
    }
}
