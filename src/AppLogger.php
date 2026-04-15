<?php

declare(strict_types=1);

namespace YtHub;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

final class AppLogger
{
    private static ?Logger $instance = null;

    public static function get(): Logger
    {
        if (self::$instance === null) {
            $dir = dirname(__DIR__) . '/storage/logs';
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                $dir = sys_get_temp_dir();
            }
            $log = new Logger('yt_hub');
            $path = $dir . '/app.log';
            $level = Level::Debug;
            $raw = $_ENV['LOG_LEVEL'] ?? getenv('LOG_LEVEL');
            $env = is_string($raw) ? $raw : '';
            if ($env !== '') {
                try {
                    $level = Level::fromName(strtoupper($env));
                } catch (\UnhandledMatchError) {
                    // ungültiger LOG_LEVEL → Default Debug
                }
            }
            $log->pushHandler(new RotatingFileHandler($path, 14, $level, true, 0644));
            self::$instance = $log;
        }
        return self::$instance;
    }
}
