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
