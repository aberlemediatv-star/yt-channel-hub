<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Facebook Page publishing via Graph API v19.
 *  - Text / link posts: POST /{page-id}/feed
 *  - Video (local file): POST /{page-id}/videos (multipart)
 *
 * Uses the Page access token stored by SocialOAuthFacebookController.
 */
final class FacebookPublisher
{
    private const GRAPH = 'https://graph.facebook.com/v19.0';

    public function publishVideoPost(SocialPost $post): string
    {
        $acct = $this->pickPageAccount($post);
        $pageId = (string) $acct->external_user_id;
        $token = (string) $acct->access_token;
        if ($pageId === '' || $token === '') {
            throw new RuntimeException('Kein Facebook Page-Token verfügbar.');
        }

        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $text = trim((string) ($payload['text'] ?? ''));
        $local = is_string($post->local_video_path) ? trim($post->local_video_path) : '';
        $ytId = is_string($post->youtube_video_id) ? trim($post->youtube_video_id) : '';

        if ($local !== '' && is_file($local)) {
            return $this->uploadLocalVideo($pageId, $token, $local, $text);
        }

        // Text / link-only post.
        $body = [];
        if ($text !== '') {
            $body['message'] = $text;
        }
        if ($ytId !== '') {
            $body['link'] = 'https://www.youtube.com/watch?v=' . rawurlencode($ytId);
            if ($text === '') {
                $body['message'] = $body['link'];
            }
        }
        if ($body === []) {
            throw new RuntimeException('Facebook-Post leer.');
        }

        $resp = Http::withToken($token)
            ->acceptJson()
            ->post(self::GRAPH . '/' . rawurlencode($pageId) . '/feed', $body);

        if (! $resp->successful()) {
            Log::warning('facebook_feed_post_failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            $err = (string) ($resp->json('error.message') ?? 'unknown');
            throw new RuntimeException('Facebook API Fehler: ' . $err);
        }

        $id = (string) ($resp->json('id') ?? '');
        if ($id === '') {
            throw new RuntimeException('Facebook API: keine Post-ID in der Antwort.');
        }

        return $id;
    }

    private function uploadLocalVideo(string $pageId, string $token, string $path, string $description): string
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('facebook_open_failed');
        }
        try {
            $req = Http::withToken($token)
                ->acceptJson()
                ->timeout(600)
                ->attach('source', $fh, basename($path));
            if ($description !== '') {
                $req = $req->attach('description', $description);
            }
            $resp = $req->post(self::GRAPH . '/' . rawurlencode($pageId) . '/videos');
        } finally {
            if (is_resource($fh)) {
                fclose($fh);
            }
        }

        if (! $resp->successful()) {
            Log::warning('facebook_video_upload_failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            $err = (string) ($resp->json('error.message') ?? 'unknown');
            throw new RuntimeException('Facebook Video-Upload Fehler: ' . $err);
        }

        $vid = (string) ($resp->json('id') ?? '');
        if ($vid === '') {
            throw new RuntimeException('Facebook Video: keine ID in der Antwort.');
        }

        return $vid;
    }

    private function pickPageAccount(SocialPost $post): SocialAccount
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $targetId = isset($payload['facebook_page_id']) ? (string) $payload['facebook_page_id'] : '';
        $q = SocialAccount::query()->where('platform', 'facebook');
        if ($targetId !== '') {
            $q->where('external_user_id', $targetId);
        }
        $acct = $q->orderByDesc('id')->first();
        if ($acct === null) {
            throw new RuntimeException('Kein Facebook-Page-Account verbunden.');
        }

        return $acct;
    }
}
