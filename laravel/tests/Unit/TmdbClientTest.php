<?php

namespace Tests\Unit;

use App\Services\Tmdb\TmdbClient;
use PHPUnit\Framework\TestCase;

final class TmdbClientTest extends TestCase
{
    public function test_is_configured_returns_false_without_key(): void
    {
        $client = new TmdbClient('');
        $this->assertFalse($client->isConfigured());
    }

    public function test_is_configured_returns_true_with_key(): void
    {
        $client = new TmdbClient('abc123');
        $this->assertTrue($client->isConfigured());
    }

    public function test_search_returns_empty_when_not_configured(): void
    {
        $client = new TmdbClient('');
        $this->assertSame([], $client->search('Matrix'));
    }

    public function test_details_returns_null_for_invalid_type(): void
    {
        $client = new TmdbClient('abc');
        $this->assertNull($client->details(123, 'person'));
    }
}
