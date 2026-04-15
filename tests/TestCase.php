<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Routen, die YtHub bootstrap laden, erwarten ein fixes Laravel-Base-Path (Tests).
     */
    protected function setUp(): void
    {
        $laravelBase = dirname(__DIR__);
        $_ENV['APP_BASE_PATH'] = $laravelBase;
        $_SERVER['APP_BASE_PATH'] = $laravelBase;

        parent::setUp();
    }
}
