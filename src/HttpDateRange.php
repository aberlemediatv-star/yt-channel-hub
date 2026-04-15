<?php

declare(strict_types=1);

namespace YtHub;

use DateTimeImmutable;

/**
 * Validiert Datums-Parameter (Y-m-d) für Berichte und Backend-Ansichten (GET/POST).
 */
final class HttpDateRange
{
    /**
     * @param array<string, mixed> $post
     * @return array{start: string, end: string}
     */
    public static function fromPost(array $post, int $defaultDaysBack = 30): array
    {
        return self::fromGet($post, $defaultDaysBack);
    }

    /**
     * @param array<string, mixed> $get
     * @return array{start: string, end: string}
     */
    public static function fromGet(array $get, int $defaultDaysBack = 30): array
    {
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $endRaw = isset($get['end']) ? trim((string) $get['end']) : '';
        $startRaw = isset($get['start']) ? trim((string) $get['start']) : '';

        $end = self::parseYmdOrNull($endRaw) ?? $today;
        $start = self::parseYmdOrNull($startRaw);

        if ($start === null) {
            $endDt = DateTimeImmutable::createFromFormat('Y-m-d', $end);
            if ($endDt === false) {
                $endDt = new DateTimeImmutable('today');
                $end = $endDt->format('Y-m-d');
            }
            $start = $endDt->modify('-' . max(0, $defaultDaysBack) . ' days')->format('Y-m-d');
        }

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return ['start' => $start, 'end' => $end];
    }

    private static function parseYmdOrNull(string $s): ?string
    {
        if ($s === '') {
            return null;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
        if ($d === false) {
            return null;
        }

        return $d->format('Y-m-d');
    }
}
