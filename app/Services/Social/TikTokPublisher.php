<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;

final class TikTokPublisher
{
    public function publishVideoPost(SocialPost $post): string
    {
        $acct = SocialAccount::query()->where('platform', 'tiktok')->orderByDesc('id')->first();
        if ($acct === null || ! is_string($acct->access_token) || $acct->access_token === '') {
            throw new \RuntimeException('Kein TikTok Account verbunden.');
        }

        // TikTok Content Posting API requires `video.upload` or `video.publish` scopes
        // and an app that has passed review. This is a stub to provide the pipeline.
        throw new \RuntimeException('TikTok Video-Publishing ist vorbereitet, aber benötigt Content Posting API Scopes (video.upload/video.publish) + App Review.');
    }
}
