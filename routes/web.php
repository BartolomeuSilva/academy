<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\VideoPlaylistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn (Request $request) => $request->user()
    ? to_route('courses.index')
    : Inertia::render('Welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/cursos')->name('dashboard');

    Route::get('cursos', [CourseController::class, 'index'])->name('courses.index');
    Route::get('cursos/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('cursos/{course:slug}/aulas/{lesson:slug}', [LessonController::class, 'show'])->name('lessons.show');

    Route::put('aulas/{lesson}/progresso', [LessonProgressController::class, 'update'])
        ->middleware('throttle:120,1')
        ->name('lessons.progress.update');

    Route::get('anexos/{attachment}', [LessonAttachmentController::class, 'show'])->name('attachments.show');

    Route::get('videos/{video}/{playlist}.m3u8', [VideoPlaylistController::class, 'show'])
        ->where('playlist', '[a-z0-9]+')
        ->name('videos.playlist');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
