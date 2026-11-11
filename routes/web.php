<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Community\ChannelController;
use App\Http\Controllers\Community\LessonDiscussionController;
use App\Http\Controllers\Community\MessageController;
use App\Http\Controllers\Community\ReactionController;
use App\Http\Controllers\Community\ReportController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonCommentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\LessonRatingController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
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
    Route::get('meus-cursos', [CourseController::class, 'library'])->name('courses.library');
    Route::get('cursos/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('cursos/{course:slug}/aulas/{lesson:slug}', [LessonController::class, 'show'])->name('lessons.show');

    Route::put('aulas/{lesson}/progresso', [LessonProgressController::class, 'update'])
        ->middleware('throttle:120,1')
        ->name('lessons.progress.update');

    Route::put('aulas/{lesson}/avaliacao', [LessonRatingController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('lessons.rating.update');

    Route::post('aulas/{lesson}/comentarios', [LessonCommentController::class, 'store'])
        ->middleware('throttle:comments')
        ->name('lessons.comments.store');
    Route::scopeBindings()->group(function () {
        Route::patch('aulas/{lesson}/comentarios/{comment}', [LessonCommentController::class, 'update'])->name('lessons.comments.update');
        Route::delete('aulas/{lesson}/comentarios/{comment}', [LessonCommentController::class, 'destroy'])->name('lessons.comments.destroy');
    });

    Route::get('materiais', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('anexos/{attachment}', [LessonAttachmentController::class, 'show'])->name('attachments.show');

    Route::prefix('comunidade')->name('community.')->group(function () {
        Route::get('/', [ChannelController::class, 'index'])->name('index');
        Route::get('aulas', [LessonDiscussionController::class, 'index'])->name('lessons');
        Route::get('{space:slug}', [ChannelController::class, 'show'])->name('channels.show');
        Route::post('{space:slug}/mensagens', [MessageController::class, 'store'])->middleware('throttle:community')->name('messages.store');

        Route::scopeBindings()->group(function () {
            Route::put('{space:slug}/mensagens/{message}', [MessageController::class, 'update'])->name('messages.update');
            Route::delete('{space:slug}/mensagens/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
            Route::post('{space:slug}/mensagens/{message}/reacoes', [ReactionController::class, 'store'])->middleware('throttle:60,1')->name('messages.reactions.store');
            Route::post('{space:slug}/mensagens/{message}/denuncia', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('messages.report');
        });
    });

    Route::get('conquistas', [AchievementController::class, 'index'])->name('achievements.index');

    Route::get('busca', [SearchController::class, 'index'])->middleware('throttle:60,1')->name('search');

    Route::get('notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notificacoes/lidas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('notificacoes/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::get('videos/{video}/{playlist}.m3u8', [VideoPlaylistController::class, 'show'])
        ->where('playlist', '[a-z0-9]+')
        ->name('videos.playlist');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
