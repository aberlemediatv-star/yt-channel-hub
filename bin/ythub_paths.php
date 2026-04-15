<?php

declare(strict_types=1);

/**
 * @return non-empty-string Laravel-Anwendungswurzel (enthält src/, vendor/, .env)
 */
function ythub_laravel_root(): string
{
    $repo = dirname(__DIR__);
    if (is_file($repo.'/laravel/src/bootstrap.php')) {
        return $repo.'/laravel';
    }
    if (is_file($repo.'/src/bootstrap.php')) {
        return $repo;
    }

    fwrite(STDERR, "YtHub: Laravel-Wurzel nicht gefunden (weder laravel/src noch flaches src/).\n");
    exit(1);
}

function ythub_require_bootstrap(): void
{
    require_once ythub_laravel_root().'/src/bootstrap.php';
}
