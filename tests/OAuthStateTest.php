<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\OAuthState;
use PHPUnit\Framework\TestCase;

final class OAuthStateTest extends TestCase
{
    public function testPlainNumericStateRejected(): void
    {
        $this->assertNull(OAuthState::verifyAndParse('42'));
        $this->assertNull(OAuthState::verifyAndParse('0'));
        $this->assertNull(OAuthState::verifyAndParse(''));
    }

    public function testRejectNonNumericGarbage(): void
    {
        $this->assertNull(OAuthState::verifyAndParse('12abc'));
        $this->assertNull(OAuthState::verifyAndParse('x:12'));
    }
}
