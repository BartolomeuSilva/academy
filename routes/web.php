<?php

use App\Http\Controllers\VideoPlaylistController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('videos/{video}/{playlist}.m3u8', [VideoPlaylistController::class, 'show'])
        ->where('playlist', '[a-z0-9]+')
        ->name('videos.playlist');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
