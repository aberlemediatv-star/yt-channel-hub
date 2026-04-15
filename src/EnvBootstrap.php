<?php

declare(strict_types=1);

namespace YtHub;

use Dotenv\Dotenv;

final class EnvBootstrap
{
    public static function load(string $projectRoot): void
    {
        $path = $projectRoot . '/.env';
        if (!is_readable($path)) {
            return;
        }
        Dotenv::createImmutable($projectRoot)->safeLoad();
    }
}
