<?php

use App\Http\Controllers\Staff\CloudFilesController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\DropboxOAuthController;
use App\Http\Controllers\Staff\GdriveOAuthController;
use App\Http\Controllers\Staff\LoginController;
use App\Http\Controllers\Staff\RevenueController;
use App\Http\Controllers\Staff\UploadController;
use App\Http\Controllers\Staff\VideosController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('/staff/upload-loc.js', static function () {
        $path = public_path('staff/upload-loc.js');
        abort_unless(is_readable($path), 404);

        return response()->file($path, ['Content-Type' => 'application/javascript; charset=utf-8']);
    });
});

Route::middleware(['web', 'yt.bootstrap', 'yt.script', 'yt.staff.auth', 'throttle:staff-login'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function (): void {
        Route::match(['get', 'post'], '/staff/login.php', [LoginController::class, 'show']);
    });

Route::middleware(['web', 'yt.bootstrap', 'yt.script', 'yt.staff.auth'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function (): void {
        Route::redirect('/staff', '/staff/index.php');
        Route::redirect('/staff/', '/staff/index.php');
        Route::post('/staff/logout.php', [LoginController::class, 'logout']);
        Route::get('/staff/index.php', [DashboardController::class, 'index']);
        Route::match(['get', 'post'], '/staff/upload.php', [UploadController::class, 'show']);
        Route::match(['get', 'post'], '/staff/videos.php', [VideosController::class, 'index']);
        Route::get('/staff/revenue.php', [RevenueController::class, 'index']);

        Route::get('/staff/oauth/gdrive/start', [GdriveOAuthController::class, 'start']);
        Route::get('/staff/oauth/gdrive/callback', [GdriveOAuthController::class, 'callback']);
        Route::get('/staff/oauth/gdrive/disconnect', [GdriveOAuthController::class, 'disconnect']);
        Route::get('/staff/oauth/dropbox/start', [DropboxOAuthController::class, 'start']);
        Route::get('/staff/oauth/dropbox/callback', [DropboxOAuthController::class, 'callback']);
        Route::get('/staff/oauth/dropbox/disconnect', [DropboxOAuthController::class, 'disconnect']);
        Route::get('/staff/cloud/files', [CloudFilesController::class, 'index']);
    });
