<?php

declare(strict_types=1);

namespace YtHub;

use Throwable;

final class Retry
{
    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public static function withBackoff(callable $fn, int $maxAttempts = 4, int $baseDelayMs = 400): mixed
    {
        $attempt = 0;
        $last = null;
        while ($attempt < $maxAttempts) {
            try {
                return $fn();
            } catch (Throwable $e) {
                $last = $e;
                $attempt++;
                if ($attempt >= $maxAttempts || !self::isRetriable($e)) {
                    throw $e;
                }
                $ms = $baseDelayMs * (2 ** ($attempt - 1));
                usleep($ms * 1000);
            }
        }
        throw $last ?? new \RuntimeException('Retry failed');
    }

    private static function isRetriable(Throwable $e): bool
    {
        $code = (int) $e->getCode();
        if ($code === 429 || $code === 500 || $code === 503) {
            return true;
        }
        $msg = strtolower($e->getMessage());
        return str_contains($msg, 'rate limit') || str_contains($msg, 'quota');
    }
}
