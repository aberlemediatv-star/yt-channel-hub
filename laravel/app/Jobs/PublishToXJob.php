<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\Social\SocialPostDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PublishToXJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $socialPostId) {}

    public function handle(SocialPostDispatcher $dispatcher): void
    {
        /** @var SocialPost|null $post */
        $post = SocialPost::query()->whereKey($this->socialPostId)->first();
        if ($post === null) {
            return;
        }
        $dispatcher->publish($post);
    }
}
