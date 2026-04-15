<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use YtHub\InstallHelper;

final class InstallController extends Controller
{
    private function hubConfigPath(): string
    {
        return base_path('config/hub.php');
    }

    public function show(Request $request): View|Response
    {
        require_once base_path('vendor/autoload.php');

        $configPath = $this->hubConfigPath();
        $alreadyInstalled = false;
        if (is_readable($configPath)) {
            try {
                /** @var mixed $existing */
                $existing = require $configPath;
                if (is_array($existing) && ! empty($existing['app']['installed'])) {
                    $alreadyInstalled = true;
                }
            } catch (\Throwable) {
                // defekte Datei: Installer darf überschreiben
            }
        }
        if ($alreadyInstalled) {
            return response()->view('system.install-already', [], 403);
        }

        $configWillOverwrite = false;
        if (is_readable($configPath)) {
            try {
                /** @var mixed $cfg */
                $cfg = require $configPath;
                $configWillOverwrite = is_array($cfg) && empty($cfg['app']['installed']);
            } catch (\Throwable) {
                $configWillOverwrite = true;
            }
        }

        $suggestedRedirect = InstallHelper::detectPublicBaseUrl().'/oauth_callback.php';

        return view('system.install', [
            'configWillOverwrite' => $configWillOverwrite,
            'done' => false,
            'errors' => [],
            'savedInternalToken' => null,
            'post' => $request->all(),
            'suggestedRedirect' => $suggestedRedirect,
        ]);
    }

    public function store(Request $request): View|Response
    {
        require_once base_path('vendor/autoload.php');

        $configPath = $this->hubConfigPath();
        $schemaPath = base_path('database/legacy_sql/schema.sql');

        $alreadyInstalled = false;
        if (is_readable($configPath)) {
            try {
                /** @var mixed $existing */
                $existing = require $configPath;
                if (is_array($existing) && ! empty($existing['app']['installed'])) {
                    $alreadyInstalled = true;
                }
            } catch (\Throwable) {
            }
        }
        if ($alreadyInstalled) {
            return response()->view('system.install-already', [], 403);
        }

        $configWillOverwrite = false;
        if (is_readable($configPath)) {
            try {
                /** @var mixed $cfg */
                $cfg = require $configPath;
                $configWillOverwrite = is_array($cfg) && empty($cfg['app']['installed']);
            } catch (\Throwable) {
                $configWillOverwrite = true;
            }
        }

        $host = trim((string) $request->input('db_host', 'localhost'));
        $port = max(1, min(65535, (int) $request->input('db_port', 3306)));
        $dbname = trim((string) $request->input('db_name', ''));
        $user = trim((string) $request->input('db_user', ''));
        $pass = (string) $request->input('db_pass', '');
        $socket = trim((string) $request->input('db_socket', ''));
        $apiKey = trim((string) $request->input('google_api_key', ''));
        $clientId = trim((string) $request->input('google_client_id', ''));
        $clientSecret = trim((string) $request->input('google_client_secret', ''));
        $redirectUri = trim((string) $request->input('google_redirect_uri', ''));

        $errors = [];
        if ($dbname === '' || $user === '') {
            $errors[] = 'Datenbankname und Benutzer sind Pflichtfelder.';
        }

        $done = false;
        $savedInternalToken = null;

        if ($errors === []) {
            try {
                $dsn = $socket !== ''
                    ? InstallHelper::buildPdoDsn($host, $port, $dbname, $socket)
                    : InstallHelper::buildPdoDsn($host, $port, $dbname, null);
                $pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
                InstallHelper::runSchema($pdo, $schemaPath);
                InstallHelper::runMigrations($pdo, base_path('database/legacy_sql/migrations'));

                if ($redirectUri === '') {
                    $redirectUri = rtrim(InstallHelper::detectPublicBaseUrl(), '/').'/oauth_callback.php';
                }

                $internalToken = bin2hex(random_bytes(32));

                $config = [
                    'app' => [
                        'installed' => true,
                        'installed_at' => gmdate('c'),
                    ],
                    'security' => [
                        'internal_token' => $internalToken,
                        'encryption_key' => '',
                    ],
                    'admin' => [
                        'password_hash' => '',
                    ],
                    'db' => [
                        'dsn' => $dsn,
                        'user' => $user,
                        'pass' => $pass,
                    ],
                    'google' => [
                        'api_key' => $apiKey,
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
                    ],
                ];
                InstallHelper::writeConfigPhp($config, $configPath);
                $savedInternalToken = $internalToken;
                $done = true;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        $suggestedRedirect = InstallHelper::detectPublicBaseUrl().'/oauth_callback.php';

        return view('system.install', [
            'configWillOverwrite' => $configWillOverwrite,
            'done' => $done,
            'errors' => $errors,
            'savedInternalToken' => $savedInternalToken,
            'post' => $request->all(),
            'suggestedRedirect' => $suggestedRedirect,
        ]);
    }
}
