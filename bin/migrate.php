#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Führt database/legacy_sql/migrations/*.sql aus (CLI).
 * Unterstützt Monorepo (…/laravel/) und flaches Deploy (nur Laravel-Verzeichnis).
 */

require_once __DIR__.'/ythub_paths.php';

ythub_require_bootstrap();
$migrationsDir = ythub_laravel_root().'/database/legacy_sql/migrations';

use YtHub\Db;
use YtHub\InstallHelper;

$pdo = Db::pdo();
InstallHelper::runMigrations($pdo, $migrationsDir);
echo "Legacy-SQL-Migrationen ausgeführt.\n";
