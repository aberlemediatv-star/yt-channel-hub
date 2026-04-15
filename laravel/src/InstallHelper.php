<?php

declare(strict_types=1);

namespace YtHub;

use PDO;
use RuntimeException;

final class InstallHelper
{
    /**
     * Zerlegt eine .sql-Datei in ausführbare Statements (MariaDB/MySQL, keine DELIMITER-Prozeduren).
     */
    public static function parseSqlFile(string $path): array
    {
        if (!is_readable($path)) {
            throw new RuntimeException('Schema-Datei nicht lesbar: ' . $path);
        }
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Schema-Datei konnte nicht gelesen werden.');
        }
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace("/^\s*$/m", '', $sql);
        $parts = preg_split('/;\s*\n/', trim($sql));
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = $p;
            }
        }
        return $out;
    }

    public static function runSchema(PDO $pdo, string $schemaPath): void
    {
        foreach (self::parseSqlFile($schemaPath) as $stmt) {
            $pdo->exec($stmt);
        }
    }

    /**
     * Führt alle *.sql im angegebenen Verzeichnis alphabetisch aus (idempotent mit IF NOT EXISTS).
     */
    public static function runMigrations(PDO $pdo, string $migrationsDir): void
    {
        if (!is_dir($migrationsDir)) {
            return;
        }
        $files = glob($migrationsDir . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            foreach (self::parseSqlFile($file) as $stmt) {
                $pdo->exec($stmt);
            }
        }
    }

    public static function buildPdoDsn(string $host, int $port, string $dbname, ?string $unixSocket = null): string
    {
        if ($unixSocket !== null && $unixSocket !== '') {
            return sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                $unixSocket,
                $dbname
            );
        }
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $dbname
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function writeConfigPhp(array $config, string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Verzeichnis config/ konnte nicht angelegt werden.');
        }
        $export = var_export($config, true);
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $export . ";\n";
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('config/hub.php konnte nicht geschrieben werden (Rechte prüfen).');
        }
    }

    public static function detectPublicBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php');
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }
        return ($https ? 'https' : 'http') . '://' . $host . $dir;
    }
}
