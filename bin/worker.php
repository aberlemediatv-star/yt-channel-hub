#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Verarbeitet genau einen pending Job (Cron jede Minute o. ä.).
 * php bin/worker.php
 */

require_once __DIR__.'/ythub_paths.php';
ythub_require_bootstrap();

use YtHub\AppLogger;
use YtHub\Db;
use YtHub\JobQueue;
use YtHub\Sync\AnalyticsSyncRunner;
use YtHub\Sync\VideoSyncRunner;

$pdo = Db::pdo();
$job = JobQueue::claimNext($pdo);
if ($job === null) {
    exit(0);
}

$id = (int) $job['id'];
$type = (string) $job['job_type'];
$log = AppLogger::get();

try {
    switch ($type) {
        case JobQueue::TYPE_VIDEO_SYNC_ALL:
            VideoSyncRunner::runAllCli();
            break;
        case JobQueue::TYPE_VIDEO_SYNC_CHANNEL:
            $cid = (int) ($job['channel_id'] ?? 0);
            if ($cid <= 0) {
                throw new \RuntimeException('channel_id fehlt');
            }
            VideoSyncRunner::runChannelCli($cid);
            break;
        case JobQueue::TYPE_ANALYTICS_SYNC_ALL:
            $days = 28;
            if (!empty($job['payload'])) {
                $p = decodePayload($job['payload']);
                if (is_array($p) && isset($p['days'])) {
                    $days = max(1, min(366, (int) $p['days']));
                }
            }
            AnalyticsSyncRunner::runAllCli($days);
            break;
        case JobQueue::TYPE_ANALYTICS_SYNC_CHANNEL:
            $cid = (int) ($job['channel_id'] ?? 0);
            $days = 28;
            if (!empty($job['payload'])) {
                $p = decodePayload($job['payload']);
                if (is_array($p) && isset($p['days'])) {
                    $days = max(1, min(366, (int) $p['days']));
                }
            }
            if ($cid <= 0) {
                throw new \RuntimeException('channel_id fehlt');
            }
            AnalyticsSyncRunner::runChannelCli($cid, $days);
            break;
        default:
            throw new \RuntimeException('Unbekannter job_type: ' . $type);
    }
    JobQueue::complete($pdo, $id);
    $log->info('Job done', ['job_id' => $id, 'type' => $type]);
} catch (\Throwable $e) {
    JobQueue::fail($pdo, $id, $e->getMessage());
    $log->error('Job failed', ['job_id' => $id, 'error' => $e->getMessage()]);
    exit(1);
}

/**
 * @param mixed $payload
 * @return mixed
 */
function decodePayload(mixed $payload): mixed
{
    if (is_array($payload)) {
        return $payload;
    }
    if (is_string($payload) && $payload !== '') {
        $d = json_decode($payload, true);
        return is_array($d) ? $d : null;
    }
    return null;
}
