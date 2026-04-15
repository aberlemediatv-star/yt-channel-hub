<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;

final class XPublisher
{
    public function __construct(
        private readonly XAccessTokenRefresher $tokenRefresher,
    ) {}

    /**
     * Text/Link-Tweets (API v2). Lokaler Video-Upload bleibt vorbereitet.
     */
    public function publishVideoPost(SocialPost $post): string
    {
        $acct = SocialAccount::query()->where('platform', 'x')->orderByDesc('id')->first();
        if ($acct === null || ! is_string($acct->access_token) || $acct->access_token === '') {
            throw new \RuntimeException('Kein X Account verbunden.');
        }

        $this->tokenRefresher->refreshIfNeeded($acct);

        $local = $post->local_video_path;
        if (is_string($local) && trim($local) !== '') {
            throw new \RuntimeException(
                'X Video-Upload aus lokaler Datei ist noch nicht angebunden (chunked media upload + Tweet mit media_id).'
            );
        }

        $text = $this->buildTweetText($post);
        if ($text === '') {
            throw new \RuntimeException('Tweet leer: payload.text und/oder youtube_video_id setzen.');
        }

        $resp = Http::withToken($acct->access_token)
            ->acceptJson()
            ->post('https://api.x.com/2/tweets', ['text' => $text]);

        if (! $resp->successful()) {
            $detail = $resp->json('detail') ?? $resp->json('title') ?? $resp->body();
            throw new \RuntimeException('X API Fehler: '.(is_string($detail) ? $detail : json_encode($detail)));
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
        if (is_string($yt) && trim($yt) !== '') {
            $chunks[] = 'https://www.youtube.com/watch?v='.rawurlencode(trim($yt));
        }
        $out = implode("\n\n", $chunks);
        if ($out === '') {
            return '';
        }
        if (mb_strlen($out, 'UTF-8') > 280) {
            return mb_substr($out, 0, 277, 'UTF-8').'...';
        }

        return $out;
    }
}
