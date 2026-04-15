<?php

use App\Http\Controllers\Admin\AdvancedFeedController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ApiKeysController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnqueueController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\LogsController;
use App\Http\Controllers\Admin\MrssFeedAdminController;
use App\Http\Controllers\Admin\StaffChannelsController;
use App\Http\Controllers\Admin\StaffManageController;
use App\Http\Controllers\Admin\StaffModulesController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'yt.bootstrap', 'yt.script', 'yt.admin.auth', 'throttle:admin-login'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function (): void {
        Route::match(['get', 'post'], '/admin/login.php', [LoginController::class, 'show']);
    });

Route::middleware(['web', 'yt.bootstrap', 'yt.script', 'yt.admin.auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function (): void {
        Route::post('/admin/logout.php', [LoginController::class, 'logout']);
        Route::get('/admin/index.php', [DashboardController::class, 'index']);

        Route::get('/admin/analytics.php', [AnalyticsController::class, 'show']);
        Route::post('/admin/analytics_export.php', [AnalyticsController::class, 'export']);
        Route::get('/admin/logs.php', [LogsController::class, 'show']);
        Route::post('/admin/enqueue.php', [EnqueueController::class, 'store']);
        Route::get('/admin/channel_edit.php', [ChannelController::class, 'edit']);
        Route::post('/admin/channel_save.php', [ChannelController::class, 'store']);
        Route::post('/admin/channel_delete.php', [ChannelController::class, 'destroy']);
        Route::match(['get', 'post'], '/admin/staff_manage.php', [StaffManageController::class, 'show']);
        Route::match(['get', 'post'], '/admin/staff_channels.php', [StaffChannelsController::class, 'show']);
        Route::match(['get', 'post'], '/admin/staff_modules.php', [StaffModulesController::class, 'show']);
    });

Route::middleware(['web', 'yt.bootstrap'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function (): void {
        Route::middleware(['internal.token'])->group(function (): void {
            Route::get('/admin/api-keys', [ApiKeysController::class, 'edit'])
                ->name('admin.api-keys');
            Route::post('/admin/api-keys', [ApiKeysController::class, 'update'])
                ->name('admin.api-keys.update');

            Route::get('/admin/mrss-feeds', [MrssFeedAdminController::class, 'index'])
                ->name('admin.mrss-feeds');

            Route::get('/admin/advanced-feeds', [AdvancedFeedController::class, 'index'])
                ->name('admin.advanced-feeds');
            Route::get('/admin/advanced-feeds/create', [AdvancedFeedController::class, 'create'])
                ->name('admin.advanced-feeds.create');
            Route::post('/admin/advanced-feeds', [AdvancedFeedController::class, 'store'])
                ->name('admin.advanced-feeds.store');
            Route::get('/admin/advanced-feeds/{feed}/edit', [AdvancedFeedController::class, 'edit'])
                ->name('admin.advanced-feeds.edit');
            Route::put('/admin/advanced-feeds/{feed}', [AdvancedFeedController::class, 'update'])
                ->name('admin.advanced-feeds.update');
            Route::delete('/admin/advanced-feeds/{feed}', [AdvancedFeedController::class, 'destroy'])
                ->name('admin.advanced-feeds.destroy');

            Route::get('/admin/advanced-feeds/{feed}/available-videos', [AdvancedFeedController::class, 'availableVideos'])
                ->name('admin.advanced-feeds.available-videos');
            Route::post('/admin/advanced-feeds/{feed}/items', [AdvancedFeedController::class, 'addItem'])
                ->name('admin.advanced-feeds.add-item');
            Route::delete('/admin/advanced-feeds/{feed}/items/{item}/remove', [AdvancedFeedController::class, 'removeItem'])
                ->name('admin.advanced-feeds.remove-item');

            Route::get('/admin/advanced-feeds/{feed}/tmdb-search', [AdvancedFeedController::class, 'tmdbSearch'])
                ->name('admin.advanced-feeds.tmdb-search');
            Route::post('/admin/advanced-feeds/{feed}/items/{item}/tmdb-apply', [AdvancedFeedController::class, 'tmdbApply'])
                ->name('admin.advanced-feeds.tmdb-apply');
            Route::post('/admin/advanced-feeds/{feed}/items/{item}/tmdb-clear', [AdvancedFeedController::class, 'tmdbClear'])
                ->name('admin.advanced-feeds.tmdb-clear');
        });
    });
