<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\SocialPostDispatcher;
use Illuminate\Console\Command;

final class SocialPostsRunDueCommand extends Command
{
    protected $signature = 'social:run-due
                            {--limit=20 : max posts to process per run}';

    protected $description = 'Publish due/retry social posts (scheduler tick).';

    public function handle(SocialPostDispatcher $dispatcher): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $now = now();

        $due = SocialPost::query()
            ->whereIn('status', [
                SocialPost::STATUS_QUEUED,
                SocialPost::STATUS_SCHEDULED,
                SocialPost::STATUS_RETRY,
            ])
            ->where(function ($q) use ($now): void {
                $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', $now);
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($due->isEmpty()) {
            $this->line('No due social posts.');

            return self::SUCCESS;
        }

        foreach ($due as $post) {
            $this->line(sprintf('Processing #%d (%s, attempt %d)', $post->id, $post->platform, (int) $post->attempts + 1));
            $dispatcher->publish($post);
        }

        $this->info('Done. Processed: ' . $due->count());

        return self::SUCCESS;
    }
}
