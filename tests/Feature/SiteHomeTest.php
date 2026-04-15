<?php

namespace Tests\Feature;

use Tests\TestCase;
use YtHub\Db;

/**
 * Smoke-Tests für die öffentliche Startseite (benötigt erreichbare YtHub-DB laut config.php / .env).
 */
final class SiteHomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            Db::pdo()->query('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('YtHub-Datenbank nicht erreichbar: '.$e->getMessage());
        }
    }

    public function test_home_root_returns_html_with_carousel_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
        $response->assertSee('site-carousel.js', false);
        $response->assertSee('lang-switcher', false);
    }

    public function test_home_index_php_alias_returns_ok(): void
    {
        $this->get('/index.php')->assertOk();
    }

    public function test_home_accepts_lang_query_without_error(): void
    {
        $response = $this->get('/?lang=en');

        $response->assertOk();
        $response->assertSee('<html lang="en"', false);
    }
}
