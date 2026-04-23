<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * X / Twitter v1.1 chunked media upload: INIT → APPEND (n chunks) → FINALIZE
 * → optional STATUS polling. Returns a media_id that can be used in a v2
 * POST /2/tweets body under `media.media_ids`.
 *
 * This is the only path X exposes for media; v2 has no media upload endpoint.
 */
final class XMediaUploader
{
    private const UPLOAD_URL = 'https://upload.twitter.com/1.1/media/upload.json';
    private const CHUNK_SIZE = 4 * 1024 * 1024; // 4 MiB — X recommends ≤5MB

    public function uploadVideo(SocialAccount $account, string $localPath): string
    {
        if (! is_file($localPath) || ! is_readable($localPath)) {
            throw new RuntimeException('x_media_file_unreadable');
        }
        $size = filesize($localPath);
        if ($size === false || $size <= 0) {
            throw new RuntimeException('x_media_empty');
        }

        $accessToken = (string) $account->access_token;
        if ($accessToken === '') {
            throw new RuntimeException('x_media_no_token');
        }

        $mime = $this->detectMime($localPath);

        // 1. INIT
        $init = Http::withToken($accessToken)
            ->asForm()
            ->acceptJson()
            ->post(self::UPLOAD_URL, [
                'command' => 'INIT',
                'media_type' => $mime,
                'media_category' => 'tweet_video',
                'total_bytes' => (string) $size,
            ]);
        if (! $init->ok()) {
            throw new RuntimeException('x_media_init_failed:' . $init->status());
        }
        $mediaId = (string) ($init->json('media_id_string') ?? '');
        if ($mediaId === '') {
            throw new RuntimeException('x_media_init_no_id');
        }

        // 2. APPEND
        $fh = fopen($localPath, 'rb');
        if ($fh === false) {
            throw new RuntimeException('x_media_open_failed');
        }
        try {
            $segment = 0;
            while (! feof($fh)) {
                $chunk = fread($fh, self::CHUNK_SIZE);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $append = Http::withToken($accessToken)
                    ->attach('media', $chunk, 'chunk.bin')
                    ->post(self::UPLOAD_URL, [
                        'command' => 'APPEND',
                        'media_id' => $mediaId,
                        'segment_index' => (string) $segment,
                    ]);
                if ($append->status() !== 204 && ! $append->successful()) {
                    throw new RuntimeException('x_media_append_failed:' . $append->status() . ':' . $segment);
                }
                $segment++;
            }
        } finally {
            fclose($fh);
        }

        // 3. FINALIZE
        $finalize = Http::withToken($accessToken)
            ->asForm()
            ->acceptJson()
            ->post(self::UPLOAD_URL, [
                'command' => 'FINALIZE',
                'media_id' => $mediaId,
            ]);
        if (! $finalize->ok()) {
            throw new RuntimeException('x_media_finalize_failed:' . $finalize->status());
        }

        // 4. Poll processing if needed.
        $processing = $finalize->json('processing_info');
        if (is_array($processing)) {
            $this->waitForProcessing($accessToken, $mediaId, (int) ($processing['check_after_secs'] ?? 3));
        }

        return $mediaId;
    }

    private function waitForProcessing(string $accessToken, string $mediaId, int $firstDelay): void
    {
        $delay = max(1, $firstDelay);
        for ($attempt = 0; $attempt < 20; $attempt++) {
            sleep($delay);
            $status = Http::withToken($accessToken)
                ->acceptJson()
                ->get(self::UPLOAD_URL, [
                    'command' => 'STATUS',
                    'media_id' => $mediaId,
                ]);
            if (! $status->ok()) {
                throw new RuntimeException('x_media_status_failed:' . $status->status());
            }
            $info = $status->json('processing_info');
            if (! is_array($info)) {
                return;
            }
            $state = (string) ($info['state'] ?? '');
            if ($state === 'succeeded') {
                return;
            }
            if ($state === 'failed') {
                throw new RuntimeException('x_media_processing_failed:' . (string) ($info['error']['name'] ?? 'unknown'));
            }
            $delay = max(1, (int) ($info['check_after_secs'] ?? $delay));
        }
        throw new RuntimeException('x_media_processing_timeout');
    }

    private function detectMime(string $path): string
    {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = @finfo_file($finfo, $path);
            @finfo_close($finfo);
            if (is_string($detected) && $detected !== '') {
                return $detected;
            }
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'mp4', 'm4v' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            default => 'application/octet-stream',
        };
    }
}
