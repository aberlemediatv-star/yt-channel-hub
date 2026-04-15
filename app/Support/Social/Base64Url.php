<?php

namespace App\Support\Social;

final class Base64Url
{
    public static function encode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
