<?php

namespace App\Services\Tmdb;

use App\Models\SocialSetting;
use Illuminate\Support\Facades\Http;

final class TmdbClient
{
    private const BASE = 'https://api.themoviedb.org/3';

    private const IMAGE_BASE = 'https://image.tmdb.org/t/p/w500';

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        if ($apiKey !== null) {
            $this->apiKey = $apiKey;
        } else {
            $db = SocialSetting::getDecrypted('tmdb.api_key', '');
            $this->apiKey = ($db !== null && $db !== '')
                ? $db
                : (string) config('services.tmdb.api_key', '');
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Multi-search (movie + tv) by query string.
     *
     * @return list<array{id: int, type: string, title: string, year: string, poster: string|null, overview: string}>
     */
    public function search(string $query, string $language = 'de'): array
    {
        if (! $this->isConfigured() || trim($query) === '') {
            return [];
        }

        $resp = Http::acceptJson()
            ->timeout(8)
            ->get(self::BASE.'/search/multi', [
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => $language,
                'include_adult' => 'false',
                'page' => 1,
            ]);

        if (! $resp->successful()) {
            return [];
        }

        $out = [];
        foreach (($resp->json('results') ?? []) as $r) {
            $type = (string) ($r['media_type'] ?? '');
            if ($type !== 'movie' && $type !== 'tv') {
                continue;
            }
            $title = (string) ($r['title'] ?? $r['name'] ?? '');
            $date = (string) ($r['release_date'] ?? $r['first_air_date'] ?? '');
            $poster = isset($r['poster_path']) ? (self::IMAGE_BASE.$r['poster_path']) : null;

            $out[] = [
                'id' => (int) $r['id'],
                'type' => $type,
                'title' => $title,
                'year' => substr($date, 0, 4),
                'poster' => $poster,
                'overview' => (string) ($r['overview'] ?? ''),
            ];

            if (count($out) >= 10) {
                break;
            }
        }

        return $out;
    }

    /**
     * Fetch full details for a movie or TV show in the requested language.
     *
     * @return array{id: int, type: string, title: string, overview: string, poster: string|null}|null
     */
    public function details(int $tmdbId, string $type, string $language = 'de'): ?array
    {
        if (! $this->isConfigured() || ($type !== 'movie' && $type !== 'tv')) {
            return null;
        }

        $resp = Http::acceptJson()
            ->timeout(8)
            ->get(self::BASE.'/'.$type.'/'.$tmdbId, [
                'api_key' => $this->apiKey,
                'language' => $language,
            ]);

        if (! $resp->successful()) {
            return null;
        }

        $d = $resp->json();
        $title = (string) ($d['title'] ?? $d['name'] ?? '');
        $poster = isset($d['poster_path']) ? (self::IMAGE_BASE.$d['poster_path']) : null;

        return [
            'id' => (int) ($d['id'] ?? $tmdbId),
            'type' => $type,
            'title' => $title,
            'overview' => (string) ($d['overview'] ?? ''),
            'poster' => $poster,
        ];
    }
}
