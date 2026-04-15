<?php

declare(strict_types=1);

namespace YtHub;

final class AdminFlash
{
    private const KEY = '_flash';

    public static function success(string $message): void
    {
        AdminAuth::startSession();
        $_SESSION[self::KEY] = ['type' => 'ok', 'message' => $message];
    }

    public static function error(string $message): void
    {
        AdminAuth::startSession();
        $_SESSION[self::KEY] = ['type' => 'err', 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    public static function pull(): ?array
    {
        AdminAuth::startSession();
        if (empty($_SESSION[self::KEY]) || !is_array($_SESSION[self::KEY])) {
            return null;
        }
        $f = $_SESSION[self::KEY];
        unset($_SESSION[self::KEY]);
        if (!isset($f['type'], $f['message'])) {
            return null;
        }
        return ['type' => (string) $f['type'], 'message' => (string) $f['message']];
    }
}
