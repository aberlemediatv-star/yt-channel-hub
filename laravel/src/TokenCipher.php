<?php

declare(strict_types=1);

namespace YtHub;

use RuntimeException;
use SodiumException;

final class TokenCipher
{
    private const PREFIX = 'enc1:';

    public function __construct(private ?string $encryptionKey)
    {
    }

    public function isEnabled(): bool
    {
        return $this->encryptionKey !== null && $this->encryptionKey !== '';
    }

    public function encrypt(string $plain): string
    {
        if (!$this->isEnabled()) {
            return $plain;
        }
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('libsodium nicht verfügbar (PHP sodium extension).');
        }
        $key = $this->deriveKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, $key);

        return self::PREFIX . base64_encode($nonce . $cipher);
    }

    /**
     * Entschlüsselt enc1:-Werte; Klartext (Legacy) wird unverändert zurückgegeben.
     */
    public function decrypt(string $stored): string
    {
        if (!str_starts_with($stored, self::PREFIX)) {
            return $stored;
        }
        if (!$this->isEnabled()) {
            throw new RuntimeException('APP_ENCRYPTION_KEY fehlt, aber verschlüsselte Tokens in der Datenbank.');
        }
        if (!function_exists('sodium_crypto_secretbox_open')) {
            throw new RuntimeException('libsodium nicht verfügbar.');
        }
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Ungültiger Token-Datensatz.');
        }
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = $this->deriveKey();
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
        if ($plain === false) {
            throw new RuntimeException('Entschlüsselung fehlgeschlagen (falscher APP_ENCRYPTION_KEY?).');
        }
        return $plain;
    }

    private function deriveKey(): string
    {
        $k = (string) $this->encryptionKey;
        return substr(hash('sha256', $k, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
