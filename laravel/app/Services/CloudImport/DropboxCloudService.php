<?php

declare(strict_types=1);

namespace App\Services\CloudImport;

use App\Models\SocialSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class DropboxCloudService
{
    private const AUTH_URL = 'https://www.dropbox.com/oauth2/authorize';

    private const TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';

    private const API = 'https://api.dropboxapi.com/2';

    private const CONTENT = 'https://content.dropboxapi.com/2';

    public function isOAuthConfigured(): bool
    {
        $k = (string) config('cloud_import.dropbox.app_key', '');
        $s = (string) config('cloud_import.dropbox.app_secret', '');

        return $k !== '' && $s !== '';
    }

    public function isConnected(): bool
    {
        $t = SocialSetting::getDecrypted('cloud.dropbox.refresh_token', '');

        return is_string($t) && $t !== '';
    }

    public function redirectUri(): string
    {
        return rtrim((string) config('app.url', ''), '/').(string) config('cloud_import.dropbox.redirect_path', '');
    }

    public function buildAuthUrl(string $state): string
    {
        if (! $this->isOAuthConfigured()) {
            throw new RuntimeException('dropbox_oauth_not_configured');
        }
        $q = http_build_query([
            'client_id' => (string) config('cloud_import.dropbox.app_key'),
            'response_type' => 'code',
            'token_access_type' => 'offline',
            'state' => $state,
            'redirect_uri' => $this->redirectUri(),
            'scope' => 'files.metadata.read files.content.read',
        ]);

        return self::AUTH_URL.'?'.$q;
    }

    public function exchangeCodeForRefreshToken(string $code): void
    {
        $res = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => (string) config('cloud_import.dropbox.app_key'),
            'client_secret' => (string) config('cloud_import.dropbox.app_secret'),
            'redirect_uri' => $this->redirectUri(),
        ]);
        if (! $res->successful()) {
            Log::warning('dropbox_token_exchange_failed', ['status' => $res->status(), 'body' => $res->body()]);
            throw new RuntimeException('dropbox_token_exchange_failed');
        }
        /** @var array<string, mixed> $j */
        $j = $res->json();
        $refresh = (string) ($j['refresh_token'] ?? '');
        if ($refresh === '') {
            throw new RuntimeException('dropbox_no_refresh');
        }
        SocialSetting::setEncrypted('cloud.dropbox.refresh_token', $refresh);
    }

    public function disconnect(): void
    {
        SocialSetting::query()->where('key', 'cloud.dropbox.refresh_token')->delete();
    }

    /**
     * Ordner + Videos unter einem Dropbox-Pfad (`""` = Wurzel).
     *
     * @return array{path: string, can_up: bool, parent_path: ?string, entries: list<array<string, mixed>>}
     */
    public function browse(string $path = ''): array
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '/') {
            $path = '';
        }
        if ($path !== '' && ! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        $apiPath = $path === '/' ? '' : $path;

        $canUp = $path !== '';
        $parentPath = $canUp ? $this->dropboxParentPath($path) : null;

        $token = $this->accessToken();
        $res = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::API.'/files/list_folder', [
                'path' => $apiPath,
                'recursive' => false,
                'include_media_info' => false,
                'limit' => 200,
            ]);
        if (! $res->successful()) {
            Log::warning('dropbox_list_failed', ['status' => $res->status(), 'body' => $res->body()]);
            throw new RuntimeException('dropbox_list_failed');
        }
        /** @var array<string, mixed> $j */
        $j = $res->json();
        /** @var list<array<string, mixed>> $raw */
        $raw = $j['entries'] ?? [];
        $maxPages = 10;
        while (! empty($j['has_more']) && isset($j['cursor']) && $maxPages-- > 0) {
            $cont = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::API.'/files/list_folder/continue', ['cursor' => (string) $j['cursor']]);
            if (! $cont->successful()) {
                break;
            }
            $j = $cont->json();
            $raw = array_merge($raw, $j['entries'] ?? []);
        }
        $videoExt = ['mp4', 'mov', 'webm', 'mkv', 'avi', 'm4v', 'mpeg', 'mpg'];
        $entries = [];
        foreach ($raw as $e) {
            $tag = (string) ($e['.tag'] ?? '');
            $name = (string) ($e['name'] ?? '');
            $disp = (string) ($e['path_display'] ?? '');
            if ($tag === 'folder') {
                $entries[] = [
                    'kind' => 'folder',
                    'path' => $disp,
                    'name' => $name,
                ];
            } elseif ($tag === 'file') {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (! in_array($ext, $videoExt, true)) {
                    continue;
                }
                $entries[] = [
                    'kind' => 'file',
                    'path' => $disp,
                    'name' => $name,
                    'size' => isset($e['size']) ? (int) $e['size'] : null,
                ];
            }
        }
        usort($entries, static function (array $a, array $b): int {
            $ka = ($a['kind'] ?? '') === 'folder' ? 0 : 1;
            $kb = ($b['kind'] ?? '') === 'folder' ? 0 : 1;
            if ($ka !== $kb) {
                return $ka <=> $kb;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return [
            'path' => $path,
            'can_up' => $canUp,
            'parent_path' => $parentPath,
            'entries' => $entries,
        ];
    }

    private function dropboxParentPath(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || $path === '/') {
            return '';
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        $pos = strrpos($path, '/');
        if ($pos === false || $pos === 0) {
            return '';
        }

        return substr($path, 0, $pos);
    }

    public function downloadToTempFile(string $dropboxPath): string
    {
        if ($dropboxPath === '' || ! str_starts_with($dropboxPath, '/')) {
            throw new RuntimeException('dropbox_bad_path');
        }
        $token = $this->accessToken();
        $tmp = sys_get_temp_dir().'/'.uniqid('dbx_', true).'.'.strtolower(pathinfo($dropboxPath, PATHINFO_EXTENSION) ?: 'bin');

        $fh = fopen($tmp, 'wb');
        if ($fh === false) {
            throw new RuntimeException('dropbox_tmp_open');
        }

        try {
            // Stream to disk so large (multi-GB) videos don't blow the PHP memory limit.
            $res = Http::withToken($token)
                ->withHeaders([
                    'Dropbox-API-Arg' => json_encode(['path' => $dropboxPath], JSON_THROW_ON_ERROR),
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withOptions(['sink' => $fh])
                ->post(self::CONTENT.'/files/download');

            if (! $res->successful()) {
                throw new RuntimeException('dropbox_download_failed');
            }
        } catch (\Throwable $e) {
            if (is_resource($fh)) {
                fclose($fh);
            }
            @unlink($tmp);
            throw $e;
        } finally {
            if (is_resource($fh)) {
                fclose($fh);
            }
        }

        if (! is_readable($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            throw new RuntimeException('dropbox_empty');
        }

        return $tmp;
    }

    private function accessToken(): string
    {
        $refresh = SocialSetting::getDecrypted('cloud.dropbox.refresh_token', '');
        if (! is_string($refresh) || $refresh === '') {
            throw new RuntimeException('dropbox_not_connected');
        }
        $res = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh,
            'client_id' => (string) config('cloud_import.dropbox.app_key'),
            'client_secret' => (string) config('cloud_import.dropbox.app_secret'),
        ]);
        if (! $res->successful()) {
            Log::warning('dropbox_refresh_failed', ['status' => $res->status(), 'body' => $res->body()]);
            throw new RuntimeException('dropbox_refresh_failed');
        }
        /** @var array<string, mixed> $j */
        $j = $res->json();
        $access = (string) ($j['access_token'] ?? '');
        if ($access === '') {
            throw new RuntimeException('dropbox_no_access');
        }

        return $access;
    }
}
