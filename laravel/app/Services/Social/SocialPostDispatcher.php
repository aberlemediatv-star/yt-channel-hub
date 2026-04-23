<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispatches a due SocialPost to the correct platform publisher and applies
 * retry/backoff policy on failure. Called by the scheduler or worker.
 */
final class SocialPostDispatcher
{
    public function __construct(
        private readonly XPublisher $xPublisher,
        private readonly TikTokPublisher $tiktokPublisher,
        private readonly FacebookPublisher $facebookPublisher,
        private readonly InstagramPublisher $instagramPublisher,
        private readonly LinkedInPublisher $linkedinPublisher,
    ) {}

    public function publish(SocialPost $post): void
    {
        $now = now();
        $post->attempts = (int) $post->attempts + 1;
        $post->last_attempt_at = $now;
        $post->status = SocialPost::STATUS_PROCESSING;
        $post->error_message = null;
        $post->save();

        try {
            $externalId = match ($post->platform) {
                'x' => $this->xPublisher->publishVideoPost($post),
                'tiktok' => $this->tiktokPublisher->publishVideoPost($post),
                'facebook' => $this->facebookPublisher->publishVideoPost($post),
                'instagram' => $this->instagramPublisher->publishVideoPost($post),
                'linkedin' => $this->linkedinPublisher->publishVideoPost($post),
                default => throw new \RuntimeException('platform_not_supported:' . (string) $post->platform),
            };

            $post->external_id = $externalId;
            $post->status = SocialPost::STATUS_PUBLISHED;
            $post->next_attempt_at = null;
            $post->save();

            Log::info('social_post_published', [
                'id' => $post->id,
                'platform' => $post->platform,
                'external_id' => $externalId,
                'attempt' => $post->attempts,
            ]);
        } catch (Throwable $e) {
            $this->handleFailure($post, $e);
        }
    }

    private function handleFailure(SocialPost $post, Throwable $e): void
    {
        $messageShort = mb_substr($e->getMessage(), 0, 900);
        $attempts = (int) $post->attempts;
        $max = max(1, (int) ($post->max_attempts ?: 5));

        if ($attempts >= $max) {
            $post->status = SocialPost::STATUS_FAILED;
            $post->next_attempt_at = null;
            $post->error_message = $messageShort;
            $post->save();
            Log::error('social_post_failed_final', [
                'id' => $post->id,
                'platform' => $post->platform,
                'attempts' => $attempts,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $backoff = SocialPost::backoffSeconds($attempts);
        $post->status = SocialPost::STATUS_RETRY;
        $post->next_attempt_at = now()->addSeconds($backoff);
        $post->error_message = $messageShort;
        $post->save();

        Log::warning('social_post_retry_scheduled', [
            'id' => $post->id,
            'platform' => $post->platform,
            'attempts' => $attempts,
            'next_in_s' => $backoff,
            'error' => $e->getMessage(),
        ]);
    }
}
