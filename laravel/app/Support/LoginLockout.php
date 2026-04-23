<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Per-identifier (username or IP) rate limiter with a hard lock after too
 * many failed attempts. Backed by the framework cache; no DB changes needed.
 */
final class LoginLockout
{
    public const MAX_ATTEMPTS = 10;
    public const WINDOW_SECONDS = 600;   // 10 min rolling window
    public const LOCK_SECONDS = 1800;    // 30 min hard lock after max attempts

    public static function key(string $scope, string $identifier): string
    {
        $normalized = strtolower(trim($identifier));

        return 'login_lockout:' . $scope . ':' . hash('xxh128', $normalized);
    }

    public static function isLocked(string $scope, string $identifier): bool
    {
        $lockKey = self::key($scope, $identifier) . ':lock';

        return (bool) Cache::get($lockKey, false);
    }

    public static function secondsUntilUnlock(string $scope, string $identifier): int
    {
        $lockKey = self::key($scope, $identifier) . ':lock';
        $ttl = Cache::get($lockKey . ':exp', 0);
        if (! is_numeric($ttl)) {
            return 0;
        }
        $remaining = (int) $ttl - time();

        return max(0, $remaining);
    }

    /**
     * Call after a failed login. Returns true when the caller just crossed
     * the threshold and the lock was engaged.
     */
    public static function registerFailure(string $scope, string $identifier): bool
    {
        $base = self::key($scope, $identifier);
        $count = (int) Cache::get($base . ':count', 0) + 1;
        Cache::put($base . ':count', $count, self::WINDOW_SECONDS);

        if ($count >= self::MAX_ATTEMPTS) {
            $until = time() + self::LOCK_SECONDS;
            Cache::put($base . ':lock', true, self::LOCK_SECONDS);
            Cache::put($base . ':lock:exp', $until, self::LOCK_SECONDS);

            return true;
        }

        return false;
    }

    /**
     * Call after a successful login to reset the counter.
     */
    public static function clear(string $scope, string $identifier): void
    {
        $base = self::key($scope, $identifier);
        Cache::forget($base . ':count');
        Cache::forget($base . ':lock');
        Cache::forget($base . ':lock:exp');
    }
}
