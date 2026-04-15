<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testValidateFailsOnWrongToken(): void
    {
        $t = Csrf::token();
        $this->assertNotSame('', $t);
        $this->assertFalse(Csrf::validate('wrong'));
    }

    public function testValidateSucceeds(): void
    {
        $t = Csrf::token();
        $this->assertTrue(Csrf::validate($t));
    }
}
