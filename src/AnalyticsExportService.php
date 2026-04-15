<?php

declare(strict_types=1);

namespace YtHub;

use PDO;

/**
 * Export von analytics_daily (Kanäle, Tageswerte) als CSV — Excel (UTF-8) oder SAP-typisch (Semikolon, DE-Zahlen).
 */
final class AnalyticsExportService
{
    public const FORMAT_EXCEL = 'excel';

    public const FORMAT_SAP = 'sap';

    /** JSON-Array (UTF-8), Rohzeilen wie bei CSV — für APIs/Skripte. */
    public const FORMAT_JSON = 'json';

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchDailyRows(PDO $pdo, string $startDate, string $endDate, ?int $channelId = null): array
    {
        if ($channelId !== null && $channelId > 0) {
            $st = $pdo->prepare(
                'SELECT c.id AS channel_id, c.slug, c.title AS channel_title, c.youtube_channel_id,
                        a.report_date, a.views, a.watch_time_minutes, a.subscribers_gained,
                        a.estimated_revenue, a.estimated_ad_revenue, a.synced_at
                 FROM analytics_daily a
                 INNER JOIN channels c ON c.id = a.channel_id
                 WHERE a.report_date BETWEEN ? AND ? AND c.id = ?
                 ORDER BY c.sort_order ASC, a.report_date ASC'
            );
            $st->execute([$startDate, $endDate, $channelId]);
        } else {
            $st = $pdo->prepare(
                'SELECT c.id AS channel_id, c.slug, c.title AS channel_title, c.youtube_channel_id,
                        a.report_date, a.views, a.watch_time_minutes, a.subscribers_gained,
                        a.estimated_revenue, a.estimated_ad_revenue, a.synced_at
                 FROM analytics_daily a
                 INNER JOIN channels c ON c.id = a.channel_id
                 WHERE a.report_date BETWEEN ? AND ?
                 ORDER BY c.sort_order ASC, c.id ASC, a.report_date ASC'
            );
            $st->execute([$startDate, $endDate]);
        }

        return $st->fetchAll();
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function toCsv(string $format, array $rows): string
    {
        $headers = [
            'channel_id',
            'youtube_channel_id',
            'slug',
            'channel_title',
            'report_date',
            'views',
            'watch_time_minutes',
            'subscribers_gained',
            'estimated_revenue_eur',
            'estimated_ad_revenue_eur',
            'synced_at',
        ];

        $sep = $format === self::FORMAT_SAP ? ';' : ',';
        $lines = [];

        if ($format === self::FORMAT_EXCEL) {
            $lines[] = "\xEF\xBB\xBF";
        }

        $lines[] = $this->csvLine($headers, $sep, $format === self::FORMAT_SAP);

        foreach ($rows as $r) {
            $rev = $r['estimated_revenue'] ?? null;
            $adRev = $r['estimated_ad_revenue'] ?? null;
            $line = [
                (string) ($r['channel_id'] ?? ''),
                (string) ($r['youtube_channel_id'] ?? ''),
                (string) ($r['slug'] ?? ''),
                (string) ($r['channel_title'] ?? ''),
                isset($r['report_date']) ? (string) $r['report_date'] : '',
                (string) ($r['views'] ?? '0'),
                $this->formatNumber((float) ($r['watch_time_minutes'] ?? 0), $format),
                (string) ($r['subscribers_gained'] ?? '0'),
                $rev === null ? '' : $this->formatNumber((float) $rev, $format),
                $adRev === null ? '' : $this->formatNumber((float) $adRev, $format),
                isset($r['synced_at']) ? (string) $r['synced_at'] : '',
            ];
            $lines[] = $this->csvLine($line, $sep, $format === self::FORMAT_SAP);
        }

        return implode('', $lines);
    }

    private function formatNumber(float $n, string $format): string
    {
        if ($format === self::FORMAT_SAP) {
            return str_replace('.', ',', (string) round($n, 6));
        }

        return (string) $n;
    }

    /**
     * @param list<string> $fields
     */
    private function csvLine(array $fields, string $separator, bool $sapQuote): string
    {
        $out = [];
        foreach ($fields as $f) {
            $s = (string) $f;
            if ($sapQuote || str_contains($s, $separator) || str_contains($s, '"') || str_contains($s, "\n") || str_contains($s, "\r")) {
                $s = '"' . str_replace('"', '""', $s) . '"';
            }
            $out[] = $s;
        }

        return implode($separator, $out) . "\r\n";
    }

    public static function filenameSuffix(string $format): string
    {
        return match ($format) {
            self::FORMAT_SAP => 'sap',
            self::FORMAT_JSON => 'json',
            default => 'excel',
        };
    }
}
