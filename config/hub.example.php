<?php

declare(strict_types=1);

/**
 * Kopie nach config/hub.php oder Installation über den Web-Installer.
 *
 * Geheimnisse: bevorzugt .env (siehe .env.example) — überschreibt Werte aus dieser Datei.
 *
 * Plesk / MariaDB: Host meist localhost, Port 3306
 *
 * Sicherheit:
 * - security.internal_token: für sync_*.php, oauth_start, Backend (oder nur CLI/Cron ohne Web-Zugriff)
 * - security.encryption_key / APP_ENCRYPTION_KEY: optional, verschlüsselt OAuth-Refresh-Tokens in der DB (libsodium)
 */

return [
    'app' => [
        'installed' => true,
        'installed_at' => '2026-01-01T00:00:00+00:00',
    ],
    'admin' => [
        'password_hash' => '',
    ],
    'security' => [
        'internal_token' => '',
        'encryption_key' => '',
    ],
    'db' => [
        'dsn' => 'mysql:host=localhost;port=3306;dbname=yt_hub;charset=utf8mb4',
        'user' => 'yt_hub_user',
        'pass' => '',
    ],
    // OAuth-Scopes: siehe src/bootstrap.php (u. a. youtube.upload, youtube.force-ssl für Untertitel).
    'google' => [
        'api_key' => '',
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'https://ihre-domain.tld/oauth_callback.php',
    ],
];
