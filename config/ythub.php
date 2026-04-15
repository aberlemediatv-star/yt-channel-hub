<?php

declare(strict_types=1);

return [
    /**
     * Öffentliche Startseite: Antwort aus Laravel-Cache (Sekunden).
     * 0 = aus (Standard). Für Produktion z. B. 60–300, wenn Daten kurz veraltet sein dürfen.
     */
    'public_home_cache_ttl' => (int) env('PUBLIC_HOME_CACHE_TTL', 0),
];
