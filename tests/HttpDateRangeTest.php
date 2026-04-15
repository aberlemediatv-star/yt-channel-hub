<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\HttpDateRange;
use PHPUnit\Framework\TestCase;

final class HttpDateRangeTest extends TestCase
{
    public function testDefaultsToLast30DaysWhenOnlyEndProvided(): void
    {
        $r = HttpDateRange::fromGet(['end' => '2025-03-22'], 30);
        $this->assertSame('2025-03-22', $r['end']);
        $this->assertSame('2025-02-20', $r['start']);
    }

    public function testInvalidEndFallsBackToToday(): void
    {
        $r = HttpDateRange::fromGet(['end' => 'not-a-date'], 30);
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $this->assertSame($today, $r['end']);
    }

    public function testSwapsWhenStartAfterEnd(): void
    {
        $r = HttpDateRange::fromGet(['start' => '2025-03-22', 'end' => '2025-01-01'], 30);
        $this->assertSame('2025-01-01', $r['start']);
        $this->assertSame('2025-03-22', $r['end']);
    }

    public function testInvalidStartUsesDefaultRangeFromValidEnd(): void
    {
        $r = HttpDateRange::fromGet(['start' => 'bad', 'end' => '2025-06-15'], 7);
        $this->assertSame('2025-06-15', $r['end']);
        $this->assertSame('2025-06-08', $r['start']);
    }

    public function testFromPostMatchesFromGet(): void
    {
        $get = HttpDateRange::fromGet(['start' => '2025-01-10', 'end' => '2025-01-20'], 30);
        $post = HttpDateRange::fromPost(['start' => '2025-01-10', 'end' => '2025-01-20'], 30);
        $this->assertSame($get, $post);
    }
}
