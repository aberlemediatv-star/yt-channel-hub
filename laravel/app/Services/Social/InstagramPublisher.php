<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Instagram Graph publishing (v19).
 *
 * IG requires the media to be hosted at a public URL — it does NOT accept
 * direct binary upload. The caller must set `payload.video_url` (e.g. an
 * S3/R2 URL of the processed file). Flow:
 *
 *   1. POST /{ig-user-id}/media          → creation_id
 *   2. GET  /{creation_id}?fields=status_code  (wait until FINISHED)
 *   3. POST /{ig-user-id}/media_publish  → media id
 */
final class InstagramPublisher
{
    private const GRAPH = 'https://graph.facebook.com/v19.0';

    public function publishVideoPost(SocialPost $post): string
    {
        $acct = $this->pickAccount($post);
        $igId = (string) $acct->external_user_id;
        $token = (string) $acct->access_token;
        if ($igId === '' || $token === '') {
            throw new RuntimeException('Kein Instagram-Account verbunden.');
        }

        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $videoUrl = (string) ($payload['video_url'] ?? '');
        $caption = trim((string) ($payload['text'] ?? $payload['caption'] ?? ''));
        if ($videoUrl === '' && ! isset($payload['image_url'])) {
            throw new RuntimeException('Instagram benötigt payload.video_url oder payload.image_url (öffentlicher Link).');
        }

        // 1. Create container.
        $createBody = [
            'caption' => mb_substr($caption, 0, 2200),
            'access_token' => $token,
        ];
        if ($videoUrl !== '') {
            $createBody['media_type'] = 'REELS';
            $createBody['video_url'] = $videoUrl;
        } else {
            $createBody['image_url'] = (string) $payload['image_url'];
        }

        $create = Http::asForm()->acceptJson()->post(self::GRAPH . '/' . rawurlencode($igId) . '/media', $createBody);
        if (! $create->ok()) {
            Log::warning('instagram_media_create_failed', ['status' => $create->status(), 'body' => $create->body()]);
            throw new RuntimeException('Instagram create fehlgeschlagen: ' . (string) ($create->json('error.message') ?? 'unknown'));
        }
        $creationId = (string) ($create->json('id') ?? '');
        if ($creationId === '') {
            throw new RuntimeException('Instagram: keine creation_id.');
        }

        // 2. Poll until the container is FINISHED (video transcoding).
        if ($videoUrl !== '') {
            $this->waitForContainer($creationId, $token);
        }

        // 3. Publish.
        $publish = Http::asForm()->acceptJson()->post(self::GRAPH . '/' . rawurlencode($igId) . '/media_publish', [
            'creation_id' => $creationId,
            'access_token' => $token,
        ]);
        if (! $publish->ok()) {
            Log::warning('instagram_publish_failed', ['status' => $publish->status(), 'body' => $publish->body()]);
            throw new RuntimeException('Instagram publish fehlgeschlagen: ' . (string) ($publish->json('error.message') ?? 'unknown'));
        }
        $id = (string) ($publish->json('id') ?? '');
        if ($id === '') {
            throw new RuntimeException('Instagram publish: keine Media-ID.');
        }

        return $id;
    }

    private function waitForContainer(string $creationId, string $token): void
    {
        $delay = 3;
        for ($i = 0; $i < 40; $i++) {
            sleep($delay);
            $status = Http::acceptJson()->get(self::GRAPH . '/' . rawurlencode($creationId), [
                'fields' => 'status_code,status',
                'access_token' => $token,
            ]);
            if (! $status->ok()) {
                throw new RuntimeException('instagram_status_failed:' . $status->status());
            }
            $code = (string) ($status->json('status_code') ?? '');
            if ($code === 'FINISHED') {
                return;
            }
            if (in_array($code, ['ERROR', 'EXPIRED'], true)) {
                throw new RuntimeException('instagram_container_' . strtolower($code));
            }
            // IN_PROGRESS / PUBLISHED
            $delay = min(15, $delay + 2);
        }
        throw new RuntimeException('instagram_container_timeout');
    }

    private function pickAccount(SocialPost $post): SocialAccount
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $targetId = isset($payload['instagram_account_id']) ? (string) $payload['instagram_account_id'] : '';
        $q = SocialAccount::query()->where('platform', 'instagram');
        if ($targetId !== '') {
            $q->where('external_user_id', $targetId);
        }
        $acct = $q->orderByDesc('id')->first();
        if ($acct === null) {
            throw new RuntimeException('Kein Instagram-Account verbunden.');
        }

        return $acct;
    }
}
