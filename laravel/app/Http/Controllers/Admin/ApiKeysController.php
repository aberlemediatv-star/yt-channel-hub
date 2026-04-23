<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\Lang;

final class ApiKeysController extends Controller
{
    private const KEYS = [
        'tmdb.api_key',
        'google.api_key',
        'google.client_id',
        'google.client_secret',
        'google.redirect_uri',
    ];

    public function edit(): View
    {
        Lang::init();
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = SocialSetting::getDecrypted($key, '');
        }

        return view('admin.api-keys', [
            'tmdbApiKey' => $values['tmdb.api_key'],
            'googleApiKey' => $values['google.api_key'],
            'googleClientId' => $values['google.client_id'],
            'googleClientSecret' => $values['google.client_secret'],
            'googleRedirectUri' => $values['google.redirect_uri'],
            'tmdbEnvSet' => config('services.tmdb.api_key', '') !== '',
            'googleEnvSet' => ($_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? '') !== '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Lang::init();
        $data = $request->validate([
            'tmdb_api_key' => ['nullable', 'string', 'max:255'],
            'google_api_key' => ['nullable', 'string', 'max:255'],
            'google_client_id' => ['nullable', 'string', 'max:255'],
            'google_client_secret' => ['nullable', 'string', 'max:512'],
            'google_redirect_uri' => ['nullable', 'url', 'max:512'],
        ]);

        SocialSetting::setEncrypted('tmdb.api_key', (string) ($data['tmdb_api_key'] ?? ''));
        SocialSetting::setEncrypted('google.api_key', (string) ($data['google_api_key'] ?? ''));
        SocialSetting::setEncrypted('google.client_id', (string) ($data['google_client_id'] ?? ''));
        SocialSetting::setEncrypted('google.client_secret', (string) ($data['google_client_secret'] ?? ''));
        SocialSetting::setEncrypted('google.redirect_uri', (string) ($data['google_redirect_uri'] ?? ''));

        return redirect()
            ->to('/admin/api-keys')
            ->with('status', 'Gespeichert.');
    }

    /**
     * Resolve a value: DB first, then env/config fallback.
     */
    public static function resolve(string $settingKey, string $fallback = ''): string
    {
        $db = SocialSetting::getDecrypted($settingKey, '');
        if ($db !== null && $db !== '') {
            return $db;
        }

        return $fallback;
    }
}
