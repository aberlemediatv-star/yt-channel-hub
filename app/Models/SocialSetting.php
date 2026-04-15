<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;

final class SocialSetting extends Model
{
    protected $table = 'social_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'encrypted',
    ];

    public $timestamps = true;

    public static function getDecrypted(string $key, ?string $default = null): ?string
    {
        /** @var self|null $row */
        $row = self::query()->where('key', $key)->first();
        if ($row === null) {
            return $default;
        }
        try {
            $v = $row->value;
        } catch (DecryptException) {
            return $default;
        }

        return is_string($v) ? $v : $default;
    }

    public static function setEncrypted(string $key, string $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
