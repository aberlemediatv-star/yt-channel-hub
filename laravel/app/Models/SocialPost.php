<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SocialPost extends Model
{
    protected $table = 'social_posts';

    protected $fillable = [
        'platform',
        'channel_id',
        'youtube_video_id',
        'local_video_path',
        'status',
        'error_message',
        'external_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
