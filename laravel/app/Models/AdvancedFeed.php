<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AdvancedFeed extends Model
{
    protected $table = 'advanced_feeds';

    protected $fillable = [
        'slug',
        'title',
        'channel_id',
        'language',
        'tmdb_enabled',
        'is_active',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'tmdb_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<AdvancedFeedItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AdvancedFeedItem::class)->orderBy('sort_order');
    }
}
