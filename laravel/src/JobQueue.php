<?php

declare(strict_types=1);

namespace YtHub;

use PDO;

final class JobQueue
{
    public const TYPE_VIDEO_SYNC_ALL = 'video_sync_all';
    public const TYPE_VIDEO_SYNC_CHANNEL = 'video_sync_channel';
    public const TYPE_ANALYTICS_SYNC_ALL = 'analytics_sync_all';
    public const TYPE_ANALYTICS_SYNC_CHANNEL = 'analytics_sync_channel';

    public static function enqueue(PDO $pdo, string $jobType, ?int $channelId = null, ?array $payload = null): int
    {
        $st = $pdo->prepare(
            'INSERT INTO jobs (job_type, channel_id, payload, status) VALUES (?,?,?,?)'
        );
        $st->execute([
            $jobType,
            $channelId,
            $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR),
            'pending',
        ]);
        return (int) $pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public static function claimNext(PDO $pdo): ?array
    {
        $pdo->beginTransaction();
        try {
            $st = $pdo->query(
                "SELECT id FROM jobs WHERE status = 'pending' ORDER BY id ASC LIMIT 1 FOR UPDATE"
            );
            $row = $st->fetch();
            if (!$row) {
                $pdo->commit();
                return null;
            }
            $id = (int) $row['id'];
            $u = $pdo->prepare(
                "UPDATE jobs SET status = 'running', started_at = NOW() WHERE id = ?"
            );
            $u->execute([$id]);
            $pdo->commit();

            $g = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
            $g->execute([$id]);
            $full = $g->fetch();
            return $full ?: null;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function complete(PDO $pdo, int $jobId): void
    {
        $st = $pdo->prepare(
            "UPDATE jobs SET status = 'done', finished_at = NOW(), error_message = NULL WHERE id = ?"
        );
        $st->execute([$jobId]);
    }

    public static function fail(PDO $pdo, int $jobId, string $message): void
    {
        $st = $pdo->prepare(
            "UPDATE jobs SET status = 'failed', finished_at = NOW(), error_message = ? WHERE id = ?"
        );
        $st->execute([mb_substr($message, 0, 1000), $jobId]);
    }

    /**
     * Rescue jobs that have been stuck in 'running' for too long. Returns the
     * number of rows that were reset to 'pending'.
     */
    public static function recoverStalled(PDO $pdo, int $stalledSeconds = 1800): int
    {
        $st = $pdo->prepare(
            "UPDATE jobs
             SET status = 'pending', started_at = NULL,
                 error_message = CONCAT(COALESCE(error_message, ''), ' [auto-requeued]')
             WHERE status = 'running' AND started_at IS NOT NULL
                   AND started_at < (NOW() - INTERVAL ? SECOND)"
        );
        $st->bindValue(1, max(60, $stalledSeconds), PDO::PARAM_INT);
        $st->execute();

        return $st->rowCount();
    }

    /**
     * @return array{pending:int, running:int, failed:int, oldest_pending_age_s:?int, stalled_running:int}
     */
    public static function stats(PDO $pdo, int $stalledSeconds = 1800): array
    {
        $out = ['pending' => 0, 'running' => 0, 'failed' => 0, 'oldest_pending_age_s' => null, 'stalled_running' => 0];
        $q = $pdo->query("SELECT status, COUNT(*) AS n FROM jobs GROUP BY status");
        foreach ($q->fetchAll() as $r) {
            $s = (string) $r['status'];
            if (isset($out[$s])) {
                $out[$s] = (int) $r['n'];
            }
        }

        $old = $pdo->query("SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MIN(created_at)) AS age FROM jobs WHERE status = 'pending'");
        $row = $old->fetch();
        if ($row && $row['age'] !== null) {
            $out['oldest_pending_age_s'] = (int) $row['age'];
        }

        $stal = $pdo->prepare("SELECT COUNT(*) AS n FROM jobs WHERE status = 'running' AND started_at IS NOT NULL AND started_at < (NOW() - INTERVAL ? SECOND)");
        $stal->bindValue(1, max(60, $stalledSeconds), PDO::PARAM_INT);
        $stal->execute();
        $row = $stal->fetch();
        $out['stalled_running'] = $row ? (int) $row['n'] : 0;

        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function listRecent(PDO $pdo, int $limit = 30): array
    {
        $lim = max(1, min(200, $limit));
        $st = $pdo->prepare('SELECT * FROM jobs ORDER BY id DESC LIMIT ?');
        $st->bindValue(1, $lim, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }
}
