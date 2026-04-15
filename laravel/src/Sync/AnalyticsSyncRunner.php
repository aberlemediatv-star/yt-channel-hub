<?php

declare(strict_types=1);

namespace YtHub\Sync;

use YtHub\AppLogger;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\HttpGuard;
use YtHub\TokenCipher;
use YtHub\YouTubeAnalyticsService;
use Monolog\Logger;
use Throwable;

final class AnalyticsSyncRunner
{
    public static function run(): void
    {
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            header('Content-Type: text/plain; charset=utf-8');
        }
        HttpGuard::requireInternalTokenOrCliForAdmin();
        $days = self::resolveDays();
        self::runAllCli($days);
    }

    public static function runAllCli(int $days): void
    {
        $end = new \DateTimeImmutable('yesterday');
        $start = $end->modify('-' . ($days - 1) . ' days');

        $log = AppLogger::get();
        $pdo = Db::pdo();
        $repo = new ChannelRepository($pdo);
        $channels = $repo->listActiveForBackend();

        $cipher = new TokenCipher(app_config()['security']['encryption_key'] ?? null);

        $client = app_google_client();
        $svc = new YouTubeAnalyticsService($client, $pdo);

        $startS = $start->format('Y-m-d');
        $endS = $end->format('Y-m-d');

        foreach ($channels as $ch) {
            self::syncOneAnalytics($pdo, $svc, $cipher, $log, $ch, $startS, $endS);
        }
        echo "\nFertig.\n";
    }

    public static function runChannelCli(int $channelId, int $days): void
    {
        $end = new \DateTimeImmutable('yesterday');
        $start = $end->modify('-' . ($days - 1) . ' days');
        $startS = $start->format('Y-m-d');
        $endS = $end->format('Y-m-d');

        $log = AppLogger::get();
        $pdo = Db::pdo();
        $repo = new ChannelRepository($pdo);
        $row = $repo->findById($channelId);
        if (!$row || !(int) ($row['is_active'] ?? 0)) {
            echo "Kanal nicht gefunden oder inaktiv.\n";
            exit(1);
        }
        $st = $pdo->prepare(
            'SELECT c.id, c.slug, c.title, c.youtube_channel_id, c.oauth_credential_id, o.refresh_token
             FROM channels c
             LEFT JOIN oauth_credentials o ON o.id = c.oauth_credential_id
             WHERE c.id = ?'
        );
        $st->execute([$channelId]);
        $ch = $st->fetch();
        if (!$ch) {
            echo "Kanal nicht gefunden.\n";
            exit(1);
        }

        $cipher = new TokenCipher(app_config()['security']['encryption_key'] ?? null);
        $client = app_google_client();
        $svc = new YouTubeAnalyticsService($client, $pdo);
        self::syncOneAnalytics($pdo, $svc, $cipher, $log, $ch, $startS, $endS);
        echo "Fertig.\n";
    }

    private static function resolveDays(): int
    {
        $days = 28;
        if (PHP_SAPI === 'cli') {
            foreach ($_SERVER['argv'] ?? [] as $arg) {
                if (preg_match('/^--days=(\d+)$/', (string) $arg, $m)) {
                    $days = max(1, min(366, (int) $m[1]));
                }
            }
        } else {
            $days = isset($_GET['days']) ? max(1, min(366, (int) $_GET['days'])) : 28;
        }
        return $days;
    }

    /**
     * @param array<string, mixed> $ch
     */
    private static function syncOneAnalytics(
        \PDO $pdo,
        YouTubeAnalyticsService $svc,
        TokenCipher $cipher,
        Logger $log,
        array $ch,
        string $startS,
        string $endS
    ): void {
        $cid = (int) $ch['id'];
        $ytId = (string) $ch['youtube_channel_id'];
        $title = (string) $ch['title'];
        $raw = $ch['refresh_token'] ?? null;
        if (!$raw) {
            echo "Überspringe {$title} (kein OAuth / refresh_token).\n";
            return;
        }
        try {
            $refresh = $cipher->decrypt((string) $raw);
        } catch (Throwable $e) {
            $log->error('Token-Entschlüsselung', ['channel' => $title, 'error' => $e->getMessage()]);
            echo "{$title}: FEHLER — Token-Entschlüsselung: " . $e->getMessage() . "\n";
            return;
        }
        $r = $svc->syncDailyRange($cid, $ytId, $refresh, $startS, $endS);
        if (isset($r['error'])) {
            $msg = $r['error'];
            $st = $pdo->prepare('UPDATE channels SET last_analytics_sync_error = ? WHERE id = ?');
            $st->execute([$msg, $cid]);
            $log->warning('Analytics-Sync', ['channel' => $title, 'error' => $msg]);
            echo "{$title}: FEHLER — {$msg}\n";
        } else {
            $st = $pdo->prepare(
                'UPDATE channels SET last_analytics_sync_at = NOW(), last_analytics_sync_error = NULL WHERE id = ?'
            );
            $st->execute([$cid]);
            $log->info('Analytics-Sync OK', ['channel' => $title, 'rows' => $r['rows']]);
            echo "{$title}: {$r['rows']} Tageszeilen ($startS … $endS).\n";
        }
    }
}
