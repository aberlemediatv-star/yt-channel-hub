#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Führt database/legacy_sql/migrations/*.sql aus (CLI, z. B. nach Update).
 * Läuft aus dem Laravel-Verzeichnis heraus (Plesk: nur laravel/ hochgeladen).
 */

$laravelBase = dirname(__DIR__);
require_once $laravelBase . '/src/bootstrap.php';

use YtHub\Db;
use YtHub\InstallHelper;

$pdo = Db::pdo();
InstallHelper::runMigrations($pdo, $laravelBase . '/database/legacy_sql/migrations');
echo "Legacy-SQL-Migrationen ausgeführt.\n";
