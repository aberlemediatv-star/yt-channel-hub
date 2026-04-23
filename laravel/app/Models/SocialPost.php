<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SocialPost extends Model
{
    protected $table = 'social_posts';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRY = 'retry';

    protected $fillable = [
        'platform',
        'channel_id',
        'youtube_video_id',
        'local_video_path',
        'status',
        'error_message',
        'external_id',
        'payload',
        'scheduled_for',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'last_attempt_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_for' => 'datetime',
        'next_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    public function isDueForPublish(\DateTimeInterface $now): bool
    {
        if ($this->scheduled_for === null) {
            return true;
        }

        return $this->scheduled_for->getTimestamp() <= $now->getTimestamp();
    }

    public function isDueForRetry(\DateTimeInterface $now): bool
    {
        if ($this->next_attempt_at === null) {
            return true;
        }

        return $this->next_attempt_at->getTimestamp() <= $now->getTimestamp();
    }

    /**
     * Exponential backoff: 1m, 5m, 15m, 1h, 6h … capped at 24h.
     */
    public static function backoffSeconds(int $attempt): int
    {
        $ladder = [60, 300, 900, 3600, 21600, 86400];
        $idx = max(0, min($attempt - 1, count($ladder) - 1));

        return $ladder[$idx];
    }
}
