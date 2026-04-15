<?php

declare(strict_types=1);

namespace YtHub;

final class ConfigMerge
{
    /**
     * Überschreibt config.php-Werte mit Umgebungsvariablen (Plesk, Docker, …).
     *
     * @param array<string, mixed> $cfg
     * @return array<string, mixed>
     */
    public static function applyEnv(array $cfg): array
    {
        if (!isset($cfg['security']) || !is_array($cfg['security'])) {
            $cfg['security'] = ['internal_token' => '', 'encryption_key' => ''];
        }
        if (!isset($cfg['admin']) || !is_array($cfg['admin'])) {
            $cfg['admin'] = ['password_hash' => ''];
        }

        $e = array_merge($_ENV, $_SERVER);

        if (!empty($e['DB_DSN'])) {
            $cfg['db']['dsn'] = (string) $e['DB_DSN'];
        }
        if (isset($e['DB_USER']) && $e['DB_USER'] !== '') {
            $cfg['db']['user'] = (string) $e['DB_USER'];
        }
        if (array_key_exists('DB_PASSWORD', $e) || array_key_exists('DB_PASS', $e)) {
            $v = $e['DB_PASSWORD'] ?? $e['DB_PASS'] ?? '';
            $cfg['db']['pass'] = (string) $v;
        }
        if (!empty($e['GOOGLE_API_KEY'])) {
            $cfg['google']['api_key'] = (string) $e['GOOGLE_API_KEY'];
        }
        if (!empty($e['GOOGLE_CLIENT_ID'])) {
            $cfg['google']['client_id'] = (string) $e['GOOGLE_CLIENT_ID'];
        }
        if (isset($e['GOOGLE_CLIENT_SECRET'])) {
            $cfg['google']['client_secret'] = (string) $e['GOOGLE_CLIENT_SECRET'];
        }
        if (!empty($e['GOOGLE_REDIRECT_URI'])) {
            $cfg['google']['redirect_uri'] = (string) $e['GOOGLE_REDIRECT_URI'];
        }
        if (!empty($e['INTERNAL_TOKEN'])) {
            $cfg['security']['internal_token'] = (string) $e['INTERNAL_TOKEN'];
        }
        if (!empty($e['APP_ENCRYPTION_KEY'])) {
            $cfg['security']['encryption_key'] = (string) $e['APP_ENCRYPTION_KEY'];
        }
        if (!empty($e['ADMIN_PASSWORD_HASH'])) {
            $cfg['admin']['password_hash'] = (string) $e['ADMIN_PASSWORD_HASH'];
        }
        if (!empty($e['ADMIN_PASSWORD_HASH_B64'])) {
            $decoded = base64_decode((string) $e['ADMIN_PASSWORD_HASH_B64'], true);
            if (is_string($decoded) && $decoded !== '') {
                $cfg['admin']['password_hash'] = $decoded;
            }
        }

        return $cfg;
    }
}
