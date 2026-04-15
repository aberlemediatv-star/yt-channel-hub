<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\TokenCipher;
use PHPUnit\Framework\TestCase;

final class TokenCipherTest extends TestCase
{
    public function testEncryptDecryptRoundTrip(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('PHP sodium extension required');
        }
        $cipher = new TokenCipher('test-key-at-least-some-length-for-hash');
        $plain = 'refresh_token_value_abc';
        $enc = $cipher->encrypt($plain);
        $this->assertNotSame($plain, $enc);
        $this->assertSame($plain, $cipher->decrypt($enc));
    }

    public function testPlaintextPassthroughWhenEncryptionDisabled(): void
    {
        $cipher = new TokenCipher(null);
        $this->assertSame('plain', $cipher->encrypt('plain'));
        $this->assertSame('plain', $cipher->decrypt('plain'));
    }
}
