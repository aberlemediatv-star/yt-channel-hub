<?php

use App\Http\Controllers\Feed\AdvancedMrssFeedController;
use App\Http\Controllers\Feed\MrssFeedController;
use App\Http\Controllers\Site\BackendController;
use App\Http\Controllers\System\GoogleOAuthController;
use App\Http\Controllers\System\HealthController;
use App\Http\Controllers\System\InstallController;
use App\Http\Controllers\System\SyncAnalyticsController;
use App\Http\Controllers\System\SyncVideosController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/health.php', [HealthController::class, 'show'])->name('system.health');

Route::middleware(['web', 'yt.bootstrap'])->withoutMiddleware([ValidateCsrfToken::class])->group(function (): void {
    Route::any('/sync_videos.php', [SyncVideosController::class, 'show'])->name('system.sync_videos');
    Route::any('/sync_analytics.php', [SyncAnalyticsController::class, 'show'])->name('system.sync_analytics');
    Route::get('/oauth_start.php', [GoogleOAuthController::class, 'start'])->name('system.oauth_start');
    Route::get('/oauth_callback.php', [GoogleOAuthController::class, 'callback'])->name('system.oauth_callback');
    Route::match(['get', 'post'], '/backend.php', [BackendController::class, 'handle'])->name('site.backend');
});

Route::middleware(['web'])->group(function (): void {
    Route::get('/install.php', [InstallController::class, 'show'])->name('system.install');
    Route::post('/install.php', [InstallController::class, 'store']);
});

// MRSS feeds — public, no auth (FAST providers fetch these directly).
Route::middleware(['web', 'yt.bootstrap'])->group(function (): void {
    Route::get('/feed/mrss', [MrssFeedController::class, 'index'])->name('feed.mrss.index');
    Route::get('/feed/mrss/{slug}', [MrssFeedController::class, 'show'])->name('feed.mrss.show')
        ->where('slug', '[a-zA-Z0-9_-]+');
    Route::get('/feed/advanced/{slug}', [AdvancedMrssFeedController::class, 'show'])->name('feed.advanced.show')
        ->where('slug', '[a-zA-Z0-9_-]+');
});
