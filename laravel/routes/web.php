<?php

use App\Http\Controllers\Admin\SocialAccountsController;
use App\Http\Controllers\Admin\SocialActivationController;
use App\Http\Controllers\Admin\SocialPostsController;
use App\Http\Controllers\Admin\SocialSettingsController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LegalController;
use App\Http\Controllers\SocialOAuthFacebookController;
use App\Http\Controllers\SocialOAuthLinkedInController;
use App\Http\Controllers\SocialOAuthMetaController;
use App\Http\Controllers\SocialOAuthTikTokController;
use App\Http\Controllers\SocialOAuthXController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('site.home');
    Route::redirect('/index', '/index.php');
    Route::get('/index.php', [HomeController::class, 'index'])->name('site.home.index');
    Route::get('/datenschutz.php', [LegalController::class, 'datenschutz'])->name('site.datenschutz');
    Route::get('/impressum.php', [LegalController::class, 'impressum'])->name('site.impressum');
    Route::redirect('/datenschutz', '/datenschutz.php');
    Route::redirect('/impressum', '/impressum.php');

    Route::middleware(['internal.token'])
        ->withoutMiddleware([ValidateCsrfToken::class])
        ->prefix('admin/social')
        ->group(function (): void {
            Route::get('/activate', [SocialActivationController::class, 'index'])->name('admin.social.activate');
            Route::get('/settings', [SocialSettingsController::class, 'edit'])->name('admin.social.settings.edit');
            Route::post('/settings', [SocialSettingsController::class, 'update'])->name('admin.social.settings.update');

            Route::get('/accounts', [SocialAccountsController::class, 'index'])->name('admin.social.accounts');
            Route::post('/accounts/{account}/disconnect', [SocialAccountsController::class, 'disconnect'])
                ->name('admin.social.accounts.disconnect');
            Route::get('/posts', [SocialPostsController::class, 'index'])->name('admin.social.posts');
            Route::post('/posts/x', [SocialPostsController::class, 'enqueueX'])->name('admin.social.posts.enqueue-x');
        });

    // OAuth callbacks must stay public (provider redirect); start is gated like other admin/social URLs.
    Route::get('/oauth/x/callback', [SocialOAuthXController::class, 'callback'])->name('oauth.x.callback');
    Route::get('/oauth/tiktok/callback', [SocialOAuthTikTokController::class, 'callback'])->name('oauth.tiktok.callback');
    Route::get('/oauth/facebook/callback', [SocialOAuthFacebookController::class, 'callback'])->name('oauth.facebook.callback');
    Route::get('/oauth/meta/callback', [SocialOAuthMetaController::class, 'callback'])->name('oauth.meta.callback');
    Route::get('/oauth/linkedin/callback', [SocialOAuthLinkedInController::class, 'callback'])->name('oauth.linkedin.callback');

    Route::middleware(['internal.token'])
        ->group(function (): void {
            Route::get('/oauth/x/start', [SocialOAuthXController::class, 'start'])->name('oauth.x.start');
            Route::get('/oauth/tiktok/start', [SocialOAuthTikTokController::class, 'start'])->name('oauth.tiktok.start');
            Route::get('/oauth/facebook/start', [SocialOAuthFacebookController::class, 'start'])->name('oauth.facebook.start');
            Route::get('/oauth/meta/start', [SocialOAuthMetaController::class, 'start'])->name('oauth.meta.start');
            Route::get('/oauth/linkedin/start', [SocialOAuthLinkedInController::class, 'start'])->name('oauth.linkedin.start');
        });
});
