<?php

declare(strict_types=1);

namespace YtHub;

use PDO;

require_once __DIR__ . '/bootstrap.php';

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = app_config()['db'];
            self::$pdo = new PDO(
                $c['dsn'],
                $c['user'],
                $c['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }
        return self::$pdo;
    }
}
