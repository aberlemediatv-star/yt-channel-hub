<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\Retry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RetryTest extends TestCase
{
    public function testSucceedsOnFirstAttempt(): void
    {
        $n = 0;
        $r = Retry::withBackoff(function () use (&$n) {
            $n++;
            return 42;
        });
        $this->assertSame(42, $r);
        $this->assertSame(1, $n);
    }

    public function testRetriesThenSucceeds(): void
    {
        $n = 0;
        $r = Retry::withBackoff(function () use (&$n) {
            $n++;
            if ($n < 2) {
                throw new RuntimeException('rate limit', 429);
            }
            return 'ok';
        }, 4, 1);
        $this->assertSame('ok', $r);
        $this->assertSame(2, $n);
    }
}
