<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// =========================================================================
// Routes to run Artisan Commands from Browser (Useful for cPanel)
// =========================================================================

Route::get('/cmd/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return '<h1>Optimize cache cleared successfully!</h1><br><a href="/">Go Back</a>';
});

Route::get('/cmd/config-cache', function () {
    Artisan::call('config:cache');
    return '<h1>Configuration cached successfully!</h1><br><a href="/">Go Back</a>';
});

Route::get('/cmd/route-cache', function () {
    Artisan::call('route:cache');
    return '<h1>Routes cached successfully!</h1><br><a href="/">Go Back</a>';
});

Route::get('/cmd/view-clear', function () {
    Artisan::call('view:clear');
    return '<h1>View cache cleared successfully!</h1><br><a href="/">Go Back</a>';
});

Route::get('/cmd/storage-link', function () {
    Artisan::call('storage:link');
    return '<h1>Storage linked successfully!</h1><br><a href="/">Go Back</a>';
});

Route::get('/cmd/migrate', function () {
    Artisan::call('migrate', ['--force' => true]);
    return '<h1>Database Migrated successfully!</h1><br><a href="/">Go Back</a>';
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
