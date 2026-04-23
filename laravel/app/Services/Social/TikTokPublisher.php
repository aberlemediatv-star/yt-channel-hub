<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * TikTok Content Posting API — direct post of a local video.
 *
 *   1. POST /v2/post/publish/video/init/  (source=FILE_UPLOAD)
 *   2. PUT {upload_url} for each chunk with Range headers (resumable)
 *   3. Poll /v2/post/publish/status/fetch/ until publish_status=PUBLISH_COMPLETE
 *
 * Requires the TikTok app to have the `video.upload` and `video.publish`
 * scopes and to have passed App Review — until then the API returns
 * scope_not_authorized which we surface as a clear error.
 */
final class TikTokPublisher
{
    private const API = 'https://open.tiktokapis.com';
    private const CHUNK = 10 * 1024 * 1024; // 10 MB per chunk (TikTok recommends 5–64MB)

    public function publishVideoPost(SocialPost $post): string
    {
        $acct = SocialAccount::query()->where('platform', 'tiktok')->orderByDesc('id')->first();
        if ($acct === null || ! is_string($acct->access_token) || $acct->access_token === '') {
            throw new RuntimeException('Kein TikTok Account verbunden.');
        }

        $local = is_string($post->local_video_path) ? trim($post->local_video_path) : '';
        if ($local === '' || ! is_file($local) || ! is_readable($local)) {
            throw new RuntimeException('TikTok benötigt eine lokale Videodatei (local_video_path).');
        }
        $size = filesize($local);
        if ($size === false || $size <= 0) {
            throw new RuntimeException('TikTok Videodatei leer.');
        }

        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $title = trim((string) ($payload['text'] ?? $payload['title'] ?? ''));
        if ($title === '') {
            $title = 'New video';
        }

        $chunkSize = min(self::CHUNK, $size);
        $totalChunks = (int) ceil($size / $chunkSize);

        // 1. INIT direct post
        $init = Http::withToken($acct->access_token)
            ->acceptJson()
            ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
            ->post(self::API . '/v2/post/publish/video/init/', [
                'post_info' => [
                    'title' => mb_substr($title, 0, 2200),
                    'privacy_level' => 'SELF_ONLY', // safest default; creator can publish later
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                ],
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $size,
                    'chunk_size' => $chunkSize,
                    'total_chunk_count' => $totalChunks,
                ],
            ]);

        if (! $init->ok()) {
            Log::warning('tiktok_init_failed', ['status' => $init->status(), 'body' => $init->body()]);
            throw new RuntimeException($this->translateTikTokError($init));
        }
        $uploadUrl = (string) ($init->json('data.upload_url') ?? '');
        $publishId = (string) ($init->json('data.publish_id') ?? '');
        if ($uploadUrl === '' || $publishId === '') {
            throw new RuntimeException('TikTok init: fehlende upload_url/publish_id.');
        }

        // 2. PUT chunks to upload_url using resumable byte ranges.
        $this->uploadChunks($uploadUrl, $local, $size, $chunkSize);

        // 3. Poll status until terminal state.
        return $this->waitForPublish($acct, $publishId);
    }

    private function uploadChunks(string $uploadUrl, string $path, int $size, int $chunkSize): void
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('tiktok_open_failed');
        }

        try {
            $offset = 0;
            while ($offset < $size) {
                $remaining = $size - $offset;
                $thisChunk = min($chunkSize, $remaining);
                $bytes = fread($fh, $thisChunk);
                if ($bytes === false || $bytes === '') {
                    throw new RuntimeException('tiktok_chunk_read_failed');
                }

                $rangeEnd = $offset + strlen($bytes) - 1;
                $res = Http::withHeaders([
                    'Content-Type' => 'video/mp4',
                    'Content-Range' => 'bytes ' . $offset . '-' . $rangeEnd . '/' . $size,
                    'Content-Length' => (string) strlen($bytes),
                ])
                    ->timeout(120)
                    ->withBody($bytes, 'video/mp4')
                    ->put($uploadUrl);

                // TikTok returns 206 for partial upload completed, 201/200 for final.
                if (! in_array($res->status(), [200, 201, 206], true)) {
                    Log::warning('tiktok_upload_chunk_failed', [
                        'status' => $res->status(),
                        'offset' => $offset,
                        'body' => $res->body(),
                    ]);
                    throw new RuntimeException('tiktok_upload_chunk_failed:' . $res->status());
                }

                $offset += strlen($bytes);
            }
        } finally {
            fclose($fh);
        }
    }

    private function waitForPublish(SocialAccount $acct, string $publishId): string
    {
        $delay = 3;
        for ($i = 0; $i < 30; $i++) {
            sleep($delay);
            $status = Http::withToken($acct->access_token)
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json; charset=UTF-8'])
                ->post(self::API . '/v2/post/publish/status/fetch/', ['publish_id' => $publishId]);

            if (! $status->ok()) {
                Log::warning('tiktok_status_failed', ['status' => $status->status(), 'body' => $status->body()]);
                throw new RuntimeException('tiktok_status_failed:' . $status->status());
            }
            $state = (string) ($status->json('data.status') ?? '');
            if ($state === 'PUBLISH_COMPLETE') {
                $postId = (string) ($status->json('data.publicaly_available_post_id.0') ?? $publishId);

                return $postId !== '' ? $postId : $publishId;
            }
            if (in_array($state, ['FAILED', 'EXPIRED'], true)) {
                $failReason = (string) ($status->json('data.fail_reason') ?? $status->json('error.message') ?? $state);
                throw new RuntimeException('tiktok_publish_failed:' . $failReason);
            }
            // PROCESSING_UPLOAD / PROCESSING_DOWNLOAD / SEND_TO_USER_INBOX
            $delay = min(15, $delay + 2);
        }
        throw new RuntimeException('tiktok_publish_timeout');
    }

    private function translateTikTokError(\Illuminate\Http\Client\Response $resp): string
    {
        $code = (string) ($resp->json('error.code') ?? '');
        $msg = (string) ($resp->json('error.message') ?? 'unknown');
        if ($code === 'scope_not_authorized' || str_contains($msg, 'scope')) {
            return 'TikTok scope fehlt (benötigt video.upload/video.publish + App Review).';
        }

        return 'TikTok init fehlgeschlagen: ' . $msg;
    }
}
