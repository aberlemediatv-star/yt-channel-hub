<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Probes formerly-404 bare paths; after friendly redirects, expects 302 to canonical URLs.
 */
final class RouteNotFoundProbeTest extends TestCase
{
    /** @return list<array{0: string, 1: string}> */
    public static function probeRedirects(): array
    {
        return [
            ['/staff', '/staff/login.php'],
            ['/staff/', '/staff/login.php'],
            ['/admin/', '/admin/login.php'],
            ['/datenschutz', '/datenschutz.php'],
            ['/impressum', '/impressum.php'],
            ['/health', '/health.php'],
            ['/backend', '/backend.php'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('probeRedirects')]
    public function test_probe_redirects_to_canonical(string $path, string $expectedLocation): void
    {
        $this->get($path)->assertRedirect($expectedLocation);
    }
}
