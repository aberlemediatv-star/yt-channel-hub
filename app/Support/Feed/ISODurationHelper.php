<?php

namespace App\Support\Feed;

final class ISODurationHelper
{
    /**
     * Convert ISO 8601 duration (PT1H2M30S) to integer seconds.
     */
    public static function toSeconds(?string $iso): int
    {
        if ($iso === null || trim($iso) === '') {
            return 0;
        }
        try {
            $interval = new \DateInterval($iso);

            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Format ISO 8601 duration string to HH:MM:SS for display.
     */
    public static function toHMS(?string $iso): string
    {
        $s = self::toSeconds($iso);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $sec);
    }
}
