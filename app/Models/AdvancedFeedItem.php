<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdvancedFeedItem extends Model
{
    protected $table = 'advanced_feed_items';

    protected $fillable = [
        'advanced_feed_id',
        'youtube_video_id',
        'sort_order',
        'tmdb_id',
        'tmdb_type',
        'tmdb_title',
        'tmdb_description',
        'tmdb_poster_url',
        'tmdb_language',
        'custom_title',
        'custom_description',
    ];

    protected $casts = [
        'advanced_feed_id' => 'integer',
        'sort_order' => 'integer',
        'tmdb_id' => 'integer',
    ];

    /** @return BelongsTo<AdvancedFeed, $this> */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(AdvancedFeed::class, 'advanced_feed_id');
    }

    /**
     * Effective title: custom > TMDB > null (fallback to YT title at render time).
     */
    public function effectiveTitle(): ?string
    {
        if (is_string($this->custom_title) && trim($this->custom_title) !== '') {
            return $this->custom_title;
        }
        if (is_string($this->tmdb_title) && trim($this->tmdb_title) !== '') {
            return $this->tmdb_title;
        }

        return null;
    }

    public function effectiveDescription(): ?string
    {
        if (is_string($this->custom_description) && trim($this->custom_description) !== '') {
            return $this->custom_description;
        }
        if (is_string($this->tmdb_description) && trim($this->tmdb_description) !== '') {
            return $this->tmdb_description;
        }

        return null;
    }
}
