<?php

declare(strict_types=1);

namespace YtHub\Sync;

use YtHub\AppLogger;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\HttpGuard;
use YtHub\YouTubeDataService;
use Monolog\Logger;
use Throwable;

final class VideoSyncRunner
{
    /** HTTP oder CLI: mit Guard (Admin/Token/CLI). */
    public static function run(): void
    {
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            header('Content-Type: text/plain; charset=utf-8');
        }
        HttpGuard::requireInternalTokenOrCliForAdmin();
        self::runAllCli();
    }

    /** Nur Worker/Cron ohne HTTP-Guard. */
    public static function runAllCli(): void
    {
        $c = app_config();
        if (($c['google']['api_key'] ?? '') === '') {
            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
            }
            echo "Setze google.api_key in config oder GOOGLE_API_KEY in .env.\n";
            exit(1);
        }

        $log = AppLogger::get();
        $pdo = Db::pdo();
        $repo = new ChannelRepository($pdo);
        $channels = $repo->listActiveForFrontend();

        $client = app_google_client();
        $data = new YouTubeDataService($client, $c['google']['api_key'], $pdo);

        $total = 0;
        foreach ($channels as $ch) {
            $total += self::syncOneChannel($pdo, $data, $log, $ch);
        }
        echo "\nFertig. Gesamt: {$total}\n";
    }

    public static function runChannelCli(int $channelId): void
    {
        $c = app_config();
        if (($c['google']['api_key'] ?? '') === '') {
            echo "API-Key fehlt.\n";
            exit(1);
        }
        $log = AppLogger::get();
        $pdo = Db::pdo();
        $repo = new ChannelRepository($pdo);
        $row = $repo->findById($channelId);
        if (!$row || !(int) ($row['is_active'] ?? 0)) {
            echo "Kanal nicht gefunden oder inaktiv.\n";
            exit(1);
        }
        $ch = [
            'id' => $row['id'],
            'youtube_channel_id' => $row['youtube_channel_id'],
            'title' => $row['title'],
        ];
        $client = app_google_client();
        $data = new YouTubeDataService($client, $c['google']['api_key'], $pdo);
        self::syncOneChannel($pdo, $data, $log, $ch);
        echo "Fertig.\n";
    }

    /**
     * @param array<string, mixed> $ch
     */
    private static function syncOneChannel(\PDO $pdo, YouTubeDataService $data, Logger $log, array $ch): int
    {
        $id = (int) $ch['id'];
        $ytId = (string) $ch['youtube_channel_id'];
        $title = (string) $ch['title'];
        try {
            $n = $data->syncVideosForChannel($id, $ytId);
            $log->info('Video-Sync OK', ['channel' => $title, 'id' => $id, 'count' => $n]);
            echo $title . ": {$n} Videos aktualisiert.\n";
            return $n;
        } catch (Throwable $e) {
            $msg = mb_substr($e->getMessage(), 0, 500);
            $st = $pdo->prepare(
                'UPDATE channels SET last_video_sync_error = ? WHERE id = ?'
            );
            $st->execute([$msg, $id]);
            $log->error('Video-Sync Fehler', ['channel' => $title, 'error' => $e->getMessage()]);
            echo $title . ": FEHLER — {$msg}\n";
            return 0;
        }
    }
}
