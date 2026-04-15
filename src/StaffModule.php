<?php

declare(strict_types=1);

namespace YtHub;

/**
 * Freigaben für Mitarbeiter (Admin steuert pro Person).
 */
final class StaffModule
{
    public const UPLOAD = 'upload';

    public const EDIT_VIDEO = 'edit_video';

    public const VIEW_REVENUE = 'view_revenue';

    /** @return array<string, bool> */
    public static function defaults(): array
    {
        return [
            self::UPLOAD => true,
            self::EDIT_VIDEO => false,
            self::VIEW_REVENUE => false,
        ];
    }

    /** @return list<string> */
    public static function allKeys(): array
    {
        return [self::UPLOAD, self::EDIT_VIDEO, self::VIEW_REVENUE];
    }

    /**
     * @param array<string, mixed> $stored
     * @return array<string, bool>
     */
    public static function mergeDefaults(array $stored): array
    {
        $out = self::defaults();
        foreach (self::allKeys() as $k) {
            if (array_key_exists($k, $stored)) {
                $out[$k] = (bool) $stored[$k];
            }
        }

        return $out;
    }
}
