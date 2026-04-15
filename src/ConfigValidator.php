<?php

declare(strict_types=1);

namespace YtHub;

use RuntimeException;

final class ConfigValidator
{
    /**
     * @param array<string, mixed> $cfg
     */
    public static function validate(array $cfg): void
    {
        if (empty($cfg['db']['dsn']) || !is_string($cfg['db']['dsn'])) {
            throw new RuntimeException('Konfiguration: db.dsn fehlt oder ist ungültig.');
        }
        if (!isset($cfg['db']['user'])) {
            throw new RuntimeException('Konfiguration: db.user fehlt.');
        }
        if (!isset($cfg['google']) || !is_array($cfg['google'])) {
            throw new RuntimeException('Konfiguration: google-Block fehlt.');
        }
        if (!isset($cfg['security']) || !is_array($cfg['security'])) {
            throw new RuntimeException('Konfiguration: security-Block fehlt (siehe config.example.php).');
        }
        if (!isset($cfg['admin']) || !is_array($cfg['admin'])) {
            throw new RuntimeException('Konfiguration: admin-Block fehlt.');
        }
    }
}
