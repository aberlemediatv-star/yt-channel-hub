<?php

declare(strict_types=1);

/**
 * Monorepo: `composer install` am Repo-Root legt vendor/ neben laravel/.
 * Standalone (z. B. Branch laravel-cloud): vendor/ liegt unter laravel/.
 */
$laravelRoot = dirname(__DIR__);

$candidates = [
    $laravelRoot.'/vendor/autoload.php',
    dirname($laravelRoot).'/vendor/autoload.php',
];

foreach ($candidates as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;

        return;
    }
}

throw new RuntimeException(
    'Composer autoload.php not found. Tried: '.implode(', ', $candidates)
);
