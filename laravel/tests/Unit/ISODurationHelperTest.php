<?php

namespace Tests\Unit;

use App\Support\Feed\ISODurationHelper;
use PHPUnit\Framework\TestCase;

final class ISODurationHelperTest extends TestCase
{
    public function test_to_seconds_typical_duration(): void
    {
        $this->assertSame(90, ISODurationHelper::toSeconds('PT1M30S'));
        $this->assertSame(3661, ISODurationHelper::toSeconds('PT1H1M1S'));
        $this->assertSame(7200, ISODurationHelper::toSeconds('PT2H'));
        $this->assertSame(45, ISODurationHelper::toSeconds('PT45S'));
    }

    public function test_to_seconds_null_and_empty(): void
    {
        $this->assertSame(0, ISODurationHelper::toSeconds(null));
        $this->assertSame(0, ISODurationHelper::toSeconds(''));
        $this->assertSame(0, ISODurationHelper::toSeconds('invalid'));
    }

    public function test_to_hms_formatting(): void
    {
        $this->assertSame('00:01:30', ISODurationHelper::toHMS('PT1M30S'));
        $this->assertSame('01:01:01', ISODurationHelper::toHMS('PT1H1M1S'));
        $this->assertSame('00:00:00', ISODurationHelper::toHMS(null));
    }
}
