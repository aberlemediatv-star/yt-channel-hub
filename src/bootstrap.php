<?php

declare(strict_types=1);

use YtHub\ConfigMerge;
use YtHub\ConfigValidator;
use YtHub\EnvBootstrap;
use Google\Client;

$appRoot = dirname(__DIR__);
require_once $appRoot . '/vendor/autoload.php';

EnvBootstrap::load($appRoot);

function app_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $base = dirname(__DIR__);
        $path = $base . '/config/hub.php';
        if (!is_readable($path)) {
            throw new RuntimeException('config/hub.php fehlt. Kopiere config/hub.example.php nach config/hub.php oder nutze den Web-Installer.');
        }
        /** @var array<string, mixed> $loaded */
        $loaded = require $path;
        $defaults = [
            'security' => [
                'internal_token' => '',
                'encryption_key' => '',
            ],
            'admin' => [
                'password_hash' => '',
            ],
        ];
        $cfg = array_replace_recursive($defaults, $loaded);
        $cfg = ConfigMerge::applyEnv($cfg);
        ConfigValidator::validate($cfg);
    }
    return $cfg;
}

/**
 * Store/retrieve Google credential overrides (set by Laravel's AppServiceProvider from DB).
 *
 * @param array<string, string>|null $set  Pass an array to store overrides, null to retrieve.
 * @return array<string, string>
 */
function app_google_overrides(?array $set = null): array
{
    static $overrides = [];
    if ($set !== null) {
        $overrides = $set;
    }
    return $overrides;
}

function app_google_client(): Client
{
    $c = app_config();
    $google = $c['google'];

    $overrides = app_google_overrides();
    foreach (['api_key', 'client_id', 'client_secret', 'redirect_uri'] as $key) {
        if (isset($overrides[$key]) && $overrides[$key] !== '') {
            $google[$key] = $overrides[$key];
        }
    }

    $client = new Client();
    $client->setApplicationName('YT Channel Hub');
    $client->setScopes([
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.force-ssl',
        'https://www.googleapis.com/auth/yt-analytics.readonly',
        'https://www.googleapis.com/auth/yt-analytics-monetary.readonly',
    ]);
    $client->setClientId($google['client_id']);
    $client->setClientSecret($google['client_secret']);
    $client->setRedirectUri($google['redirect_uri']);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    return $client;
}
