<?php

namespace App\Providers;

use App\Models\SocialSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureAuthLoginRateLimiters();
        $this->applyGoogleDbOverrides();
    }

    private function configureAuthLoginRateLimiters(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            if ($request->isMethod('get') || $request->isMethod('head')) {
                return Limit::perMinute(90)->by($request->ip());
            }

            return Limit::perMinute(8)->by($request->ip());
        });

        RateLimiter::for('staff-login', function (Request $request) {
            if ($request->isMethod('get') || $request->isMethod('head')) {
                return Limit::perMinute(90)->by($request->ip());
            }

            return Limit::perMinute(8)->by($request->ip());
        });
    }

    private function applyGoogleDbOverrides(): void
    {
        if (! function_exists('app_google_overrides')) {
            return;
        }

        try {
            if (! Schema::hasTable('social_settings')) {
                return;
            }

            $map = [
                'api_key' => SocialSetting::getDecrypted('google.api_key', ''),
                'client_id' => SocialSetting::getDecrypted('google.client_id', ''),
                'client_secret' => SocialSetting::getDecrypted('google.client_secret', ''),
                'redirect_uri' => SocialSetting::getDecrypted('google.redirect_uri', ''),
            ];

            $filtered = array_filter($map, static fn (?string $v) => $v !== null && $v !== '');
            if ($filtered !== []) {
                app_google_overrides($filtered);
            }
        } catch (\Throwable) {
            // DB not available yet (migrations pending, etc.) -- silently skip
        }
    }
}
