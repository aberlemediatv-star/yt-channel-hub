<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class XPublisher
{
    public function __construct(
        private readonly XAccessTokenRefresher $tokenRefresher,
        private readonly XMediaUploader $mediaUploader,
    ) {}

    /**
     * Text/Link tweets (v2) and local-video tweets (v1.1 chunked upload → v2 tweet).
     */
    public function publishVideoPost(SocialPost $post): string
    {
        $acct = SocialAccount::query()->where('platform', 'x')->orderByDesc('id')->first();
        if ($acct === null || ! is_string($acct->access_token) || $acct->access_token === '') {
            throw new \RuntimeException('Kein X Account verbunden.');
        }

        $this->tokenRefresher->refreshIfNeeded($acct);

        $text = $this->buildTweetText($post);
        $local = is_string($post->local_video_path) ? trim($post->local_video_path) : '';

        $body = [];
        if ($text !== '') {
            $body['text'] = $text;
        }

        if ($local !== '') {
            if (! is_file($local)) {
                throw new \RuntimeException('X Video-Datei nicht gefunden: ' . $local);
            }
            $mediaId = $this->mediaUploader->uploadVideo($acct, $local);
            $body['media'] = ['media_ids' => [$mediaId]];
        }

        if ($body === []) {
            throw new \RuntimeException('Tweet leer: text und/oder Video/youtube_video_id setzen.');
        }

        $resp = Http::withToken($acct->access_token)
            ->acceptJson()
            ->post('https://api.x.com/2/tweets', $body);

        if (! $resp->successful()) {
            Log::warning('x_tweet_post_failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            $detail = $resp->json('detail') ?? $resp->json('title') ?? 'HTTP ' . $resp->status();
            throw new \RuntimeException('X API Fehler: ' . (is_string($detail) ? $detail : json_encode($detail)));
        }

        $id = (string) ($resp->json('data.id') ?? '');
        if ($id === '') {
            throw new \RuntimeException('X API: keine Tweet-ID in der Antwort.');
        }

        return $id;
    }

    private function buildTweetText(SocialPost $post): string
    {
        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $chunks = [];
        $body = isset($payload['text']) ? trim((string) $payload['text']) : '';
        if ($body !== '') {
            $chunks[] = $body;
        }
        $yt = $post->youtube_video_id;
        // Only append a YouTube URL when we aren't uploading a local video —
        // otherwise the tweet would have both a media attachment AND a link.
        $hasLocal = is_string($post->local_video_path) && trim($post->local_video_path) !== '';
        if (! $hasLocal && is_string($yt) && trim($yt) !== '') {
            $chunks[] = 'https://www.youtube.com/watch?v=' . rawurlencode(trim($yt));
        }
        $out = implode("\n\n", $chunks);
        if ($out === '') {
            return '';
        }
        if (mb_strlen($out, 'UTF-8') > 280) {
            return mb_substr($out, 0, 277, 'UTF-8') . '...';
        }

        return $out;
    }
}
