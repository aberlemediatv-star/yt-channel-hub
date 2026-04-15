<?php

use App\Http\Controllers\LegacyBridgeController;
use Illuminate\Support\Facades\Route;

// Fallback: verbleibende public-PHP-Skripte per include (z. B. alte Bookmarks).
// Admin, Staff, System und die öffentliche Site sind über web.php, system.php, admin.php und staff.php abgebildet.

Route::any('/{path?}', [LegacyBridgeController::class, '__invoke'])
    ->where('path', '.*');
