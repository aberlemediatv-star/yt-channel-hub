<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\Lang;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LangTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->resetLangState();
    }

    protected function tearDown(): void
    {
        $this->resetLangState();
    }

    private function resetLangState(): void
    {
        $_GET = [];
        unset($_COOKIE['yt_hub_lang']);
        unset($_SESSION['lang']);
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $ref = new ReflectionClass(Lang::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        $cache = $ref->getProperty('messagesCache');
        $cache->setAccessible(true);
        $cache->setValue(null, null);
    }

    public function testTranslateGermanFromSession(): void
    {
        $_SESSION['lang'] = 'de';
        Lang::init();
        $this->assertSame('de', Lang::code());
        $this->assertSame('Verwaltung', Lang::t('admin.title'));
    }

    public function testAcceptLanguageSelectsSupportedLocale(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CH,fr;q=0.9,en;q=0.8';
        Lang::init();
        $this->assertSame('fr', Lang::code());
    }

    public function testAcceptLanguageSpanishTag(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-419,es;q=0.9,en;q=0.5';
        Lang::init();
        $this->assertSame('es', Lang::code());
    }

    public function testGetLangParameterWins(): void
    {
        $_GET['lang'] = 'th';
        $_SESSION['lang'] = 'de';
        Lang::init();
        $this->assertSame('th', Lang::code());
    }

    public function testMissingTranslationKeyReturnsKey(): void
    {
        $_SESSION['lang'] = 'en';
        Lang::init();
        $this->assertSame('definitely.missing.key.for.test', Lang::t('definitely.missing.key.for.test'));
    }

    public function testAllLocalesExposeSameMessageKeys(): void
    {
        $ref = new ReflectionClass(Lang::class);
        $method = $ref->getMethod('messages');
        $method->setAccessible(true);
        /** @var array<string, array<string, string>> $messages */
        $messages = $method->invoke(null);
        $deKeys = array_keys($messages['de']);
        sort($deKeys);
        foreach (['en', 'es', 'fr', 'th'] as $lc) {
            $keys = array_keys($messages[$lc]);
            sort($keys);
            $this->assertSame($deKeys, $keys, 'Message keys must match across locales: ' . $lc);
        }
    }

    public function testRichLegalKeysExistInAllLocales(): void
    {
        $ref = new ReflectionClass(Lang::class);
        $method = $ref->getMethod('messages');
        $method->setAccessible(true);
        $messages = $method->invoke(null);
        $richKeys = [
            'legal.ds_s4_p1_html',
            'legal.ds_s5_p1_html',
            'legal.ds_s6_p1_html',
            'legal.ds_s8_p2_html',
            'legal.im_meta_html',
            'legal.im_s8_p1_html',
            'admin.staff_channels_note_html',
            'admin.staff_note_blurb',
            'admin.api_keys_intro',
            'admin.api_keys_tmdb_blurb_html',
            'admin.api_keys_tmdb_fallback',
            'admin.api_keys_google_blurb_html',
            'admin.api_keys_google_client_fallback',
        ];
        foreach (Lang::SUPPORTED as $lc) {
            foreach ($richKeys as $k) {
                $this->assertArrayHasKey($k, $messages[$lc], $lc . ' missing ' . $k);
                $this->assertNotSame('', $messages[$lc][$k]);
            }
        }
    }
}
