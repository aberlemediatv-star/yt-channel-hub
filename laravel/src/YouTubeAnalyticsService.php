<?php

declare(strict_types=1);

namespace YtHub;

use Google\Client;
use Google\Service\YouTubeAnalytics;
use PDO;

final class YouTubeAnalyticsService
{
    public function __construct(
        private Client $google,
        private PDO $pdo
    ) {
    }

    /**
     * Tagesreport für einen Kanal; benötigt gültigen Refresh-Token mit Analytics-Zugriff.
     *
     * @return array{rows:int, error?:string}
     */
    public function syncDailyRange(
        int $channelDbId,
        string $youtubeChannelId,
        string $refreshToken,
        string $startDate,
        string $endDate
    ): array {
        $this->google->setDeveloperKey('');
        $this->google->fetchAccessTokenWithRefreshToken($refreshToken);
        if ($this->google->isAccessTokenExpired()) {
            return ['rows' => 0, 'error' => 'Access-Token ungültig oder abgelaufen'];
        }

        $analytics = new YouTubeAnalytics($this->google);
        $ids = 'channel==' . $youtubeChannelId;

        $metrics = [
            'views',
            'estimatedMinutesWatched',
            'subscribersGained',
        ];
        $monetary = ['estimatedRevenue', 'estimatedAdRevenue'];

        $baseQuery = [
            'ids' => $ids,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dimensions' => 'day',
            'metrics' => implode(',', $metrics),
            'sort' => 'day',
        ];

        $report = Retry::withBackoff(function () use ($analytics, $baseQuery) {
            return $analytics->reports->query($baseQuery);
        });
        $rowsInserted = 0;

        $monetaryByDay = [];
        try {
            $mq = array_merge($baseQuery, ['metrics' => implode(',', $monetary)]);
            $mReport = Retry::withBackoff(function () use ($analytics, $mq) {
                return $analytics->reports->query($mq);
            });
            $mHeaders = $mReport->getColumnHeaders() ?? [];
            $mNames = array_map(static fn ($h) => $h->getName(), $mHeaders);
            foreach ($mReport->getRows() ?? [] as $row) {
                $m = [];
                foreach ($mNames as $i => $name) {
                    $m[$name] = $row[$i] ?? null;
                }
                $day = isset($m['day']) ? (string) $m['day'] : null;
                if (!$day) {
                    continue;
                }
                $monetaryByDay[$day] = [
                    'estimated_revenue' => isset($m['estimatedRevenue']) ? (float) $m['estimatedRevenue'] : null,
                    'estimated_ad_revenue' => isset($m['estimatedAdRevenue']) ? (float) $m['estimatedAdRevenue'] : null,
                ];
            }
        } catch (\Throwable) {
            // Partner/Umsatz nicht verfügbar — Basis-Metriken trotzdem speichern
        }

        $headers = $report->getColumnHeaders() ?? [];
        $names = array_map(static fn ($h) => $h->getName(), $headers);

        foreach ($report->getRows() ?? [] as $row) {
            $cells = $row;
            $map = [];
            foreach ($names as $i => $name) {
                $map[$name] = $cells[$i] ?? null;
            }
            $day = isset($map['day']) ? (string) $map['day'] : null;
            if (!$day) {
                continue;
            }
            $views = (int) ($map['views'] ?? 0);
            $watchMin = (float) ($map['estimatedMinutesWatched'] ?? 0);
            $subG = (int) ($map['subscribersGained'] ?? 0);
            $rev = $monetaryByDay[$day]['estimated_revenue'] ?? null;
            $adRev = $monetaryByDay[$day]['estimated_ad_revenue'] ?? null;

            $up = $this->pdo->prepare(
                'INSERT INTO analytics_daily
                 (channel_id, report_date, views, watch_time_minutes, subscribers_gained, estimated_revenue, estimated_ad_revenue, raw_json)
                 VALUES (?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                   views = VALUES(views),
                   watch_time_minutes = VALUES(watch_time_minutes),
                   subscribers_gained = VALUES(subscribers_gained),
                   estimated_revenue = VALUES(estimated_revenue),
                   estimated_ad_revenue = VALUES(estimated_ad_revenue),
                   raw_json = VALUES(raw_json)'
            );
            $up->execute([
                $channelDbId,
                $day,
                $views,
                $watchMin,
                $subG,
                $rev,
                $adRev,
                json_encode($map, JSON_THROW_ON_ERROR),
            ]);
            $rowsInserted++;
        }

        return ['rows' => $rowsInserted];
    }

    /** @return array<string, mixed> */
    public function aggregateTotals(string $startDate, string $endDate): array
    {
        $st = $this->pdo->prepare(
            'SELECT
                SUM(a.views) AS total_views,
                SUM(a.watch_time_minutes) AS total_watch_minutes,
                SUM(a.subscribers_gained) AS total_subs_gained,
                SUM(COALESCE(a.estimated_revenue, 0)) AS total_revenue,
                SUM(COALESCE(a.estimated_ad_revenue, 0)) AS total_ad_revenue
             FROM analytics_daily a
             INNER JOIN channels c ON c.id = a.channel_id AND c.is_active = 1
             WHERE a.report_date BETWEEN ? AND ?'
        );
        $st->execute([$startDate, $endDate]);
        return $st->fetch() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function perChannelTotals(string $startDate, string $endDate): array
    {
        $st = $this->pdo->prepare(
            'SELECT c.id, c.slug, c.title,
                    SUM(a.views) AS views,
                    SUM(a.watch_time_minutes) AS watch_minutes,
                    SUM(a.subscribers_gained) AS subs_gained,
                    SUM(COALESCE(a.estimated_revenue, 0)) AS revenue,
                    SUM(COALESCE(a.estimated_ad_revenue, 0)) AS ad_revenue
             FROM channels c
             LEFT JOIN analytics_daily a ON a.channel_id = c.id AND a.report_date BETWEEN ? AND ?
             WHERE c.is_active = 1
             GROUP BY c.id, c.slug, c.title
             ORDER BY c.sort_order ASC, c.id ASC'
        );
        $st->execute([$startDate, $endDate]);
        return $st->fetchAll();
    }
}
