<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $fillable = [
        'platform',
        'label',
        'external_user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'meta',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'meta' => 'array',
    ];
}
