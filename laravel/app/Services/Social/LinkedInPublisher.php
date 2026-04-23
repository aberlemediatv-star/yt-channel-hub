<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * LinkedIn UGC posts on behalf of a person (member), plus optional video
 * asset upload via the Assets v2 "ugcPosts" flow.
 *
 *   Text-only: POST /v2/ugcPosts with shareMediaCategory = NONE
 *   Video:
 *     1. POST /v2/assets?action=registerUpload
 *     2. PUT  {uploadUrl}  (binary)
 *     3. POST /v2/ugcPosts referencing the asset URN
 */
final class LinkedInPublisher
{
    private const API = 'https://api.linkedin.com/v2';

    public function publishVideoPost(SocialPost $post): string
    {
        $acct = SocialAccount::query()->where('platform', 'linkedin')->orderByDesc('id')->first();
        if ($acct === null || ! is_string($acct->access_token) || $acct->access_token === '') {
            throw new RuntimeException('Kein LinkedIn-Account verbunden.');
        }

        $sub = (string) $acct->external_user_id;
        if ($sub === '') {
            throw new RuntimeException('LinkedIn: author-URN unbekannt.');
        }
        $authorUrn = 'urn:li:person:' . $sub;

        /** @var array<string, mixed> $payload */
        $payload = is_array($post->payload) ? $post->payload : [];
        $text = trim((string) ($payload['text'] ?? ''));
        $local = is_string($post->local_video_path) ? trim($post->local_video_path) : '';
        $ytId = is_string($post->youtube_video_id) ? trim($post->youtube_video_id) : '';

        if ($text === '' && $local === '' && $ytId === '') {
            throw new RuntimeException('LinkedIn-Post leer.');
        }

        $assetUrn = null;
        if ($local !== '' && is_file($local)) {
            $assetUrn = $this->uploadVideoAsset($acct, $authorUrn, $local);
        }

        $media = [];
        if ($assetUrn !== null) {
            $media[] = [
                'status' => 'READY',
                'media' => $assetUrn,
            ];
        } elseif ($ytId !== '') {
            $media[] = [
                'status' => 'READY',
                'originalUrl' => 'https://www.youtube.com/watch?v=' . rawurlencode($ytId),
            ];
        }

        $shareCategory = $assetUrn !== null ? 'VIDEO' : ($ytId !== '' ? 'ARTICLE' : 'NONE');

        $body = [
            'author' => $authorUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $text !== '' ? $text : ''],
                    'shareMediaCategory' => $shareCategory,
                    'media' => $media,
                ],
            ],
            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
        ];

        if ($shareCategory === 'NONE') {
            unset($body['specificContent']['com.linkedin.ugc.ShareContent']['media']);
        }

        $resp = Http::withToken($acct->access_token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->acceptJson()
            ->post(self::API . '/ugcPosts', $body);

        if (! $resp->successful()) {
            Log::warning('linkedin_ugc_post_failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            throw new RuntimeException('LinkedIn API Fehler: ' . (string) ($resp->json('message') ?? 'HTTP ' . $resp->status()));
        }

        $id = (string) ($resp->header('x-restli-id') ?? $resp->json('id') ?? '');
        if ($id === '') {
            throw new RuntimeException('LinkedIn API: keine Post-ID in der Antwort.');
        }

        return $id;
    }

    private function uploadVideoAsset(SocialAccount $acct, string $authorUrn, string $path): string
    {
        $token = (string) $acct->access_token;

        $register = Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->post(self::API . '/assets?action=registerUpload', [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-video'],
                    'owner' => $authorUrn,
                    'serviceRelationships' => [[
                        'relationshipType' => 'OWNER',
                        'identifier' => 'urn:li:userGeneratedContent',
                    ]],
                ],
            ]);

        if (! $register->ok()) {
            Log::warning('linkedin_asset_register_failed', ['status' => $register->status(), 'body' => $register->body()]);
            throw new RuntimeException('LinkedIn asset register fehlgeschlagen.');
        }
        $uploadUrl = (string) ($register->json('value.uploadMechanism.com\\.linkedin\\.digitalmedia\\.uploading\\.MediaUploadHttpRequest.uploadUrl')
            ?? $register->json('value.uploadMechanism.com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest.uploadUrl') ?? '');
        $assetUrn = (string) ($register->json('value.asset') ?? '');
        if ($uploadUrl === '' || $assetUrn === '') {
            throw new RuntimeException('LinkedIn asset register ohne uploadUrl/asset.');
        }

        // PUT the video bytes to the issued URL. LinkedIn accepts a single PUT
        // for media up to the recipe's max size.
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            throw new RuntimeException('linkedin_open_failed');
        }
        try {
            $bytes = stream_get_contents($fh);
        } finally {
            fclose($fh);
        }
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('linkedin_read_failed');
        }

        $put = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/octet-stream'])
            ->timeout(600)
            ->withBody($bytes, 'application/octet-stream')
            ->put($uploadUrl);

        if (! in_array($put->status(), [200, 201], true)) {
            Log::warning('linkedin_asset_put_failed', ['status' => $put->status(), 'body' => $put->body()]);
            throw new RuntimeException('LinkedIn asset upload fehlgeschlagen.');
        }

        return $assetUrn;
    }
}
