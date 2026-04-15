<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\Social\XPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PublishToXJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $socialPostId) {}

    public function handle(XPublisher $pub): void
    {
        /** @var SocialPost|null $post */
        $post = SocialPost::query()->whereKey($this->socialPostId)->first();
        if ($post === null) {
            return;
        }

        $post->status = 'processing';
        $post->error_message = null;
        $post->save();

        try {
            $externalId = $pub->publishVideoPost($post);
            $post->status = 'published';
            $post->external_id = $externalId;
            $post->save();
        } catch (\Throwable $e) {
            $post->status = 'failed';
            $post->error_message = $e->getMessage();
            $post->save();
        }
    }
}
