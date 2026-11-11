<?php

use App\Http\Controllers\Community\LessonDiscussionController;
use App\Http\Controllers\Community\ReplyController;
use App\Http\Controllers\Community\ReportController;
use App\Http\Controllers\Community\TopicController;
use App\Http\Controllers\Community\TopicPinController;
use App\Http\Controllers\Community\TopicSolutionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonAttachmentController;
use App\Http\Controllers\LessonCommentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\LessonRatingController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NotificationController;
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
        Route::get('/', [TopicController::class, 'index'])->name('index');
        Route::get('aulas', [LessonDiscussionController::class, 'index'])->name('lessons');
        Route::get('topicos/criar', [TopicController::class, 'create'])->name('topics.create');
        Route::post('topicos', [TopicController::class, 'store'])->middleware('throttle:community')->name('topics.store');
        Route::get('topicos/{topic}', [TopicController::class, 'show'])->name('topics.show');
        Route::put('topicos/{topic}', [TopicController::class, 'update'])->name('topics.update');
        Route::delete('topicos/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
        Route::put('topicos/{topic}/espaco', [TopicController::class, 'move'])->name('topics.move');
        Route::post('topicos/{topic}/fixar', [TopicPinController::class, 'store'])->name('topics.pin.store');
        Route::delete('topicos/{topic}/fixar', [TopicPinController::class, 'destroy'])->name('topics.pin.destroy');
        Route::delete('topicos/{topic}/solucao', [TopicSolutionController::class, 'destroy'])->name('topics.solution.destroy');
        Route::post('topicos/{topic}/denuncia', [ReportController::class, 'topic'])->middleware('throttle:10,1')->name('topics.report');
        Route::post('topicos/{topic}/respostas', [ReplyController::class, 'store'])->middleware('throttle:community')->name('topics.replies.store');

        Route::scopeBindings()->group(function () {
            Route::post('topicos/{topic}/solucao/{reply}', [TopicSolutionController::class, 'store'])->name('topics.solution.store');
            Route::put('topicos/{topic}/respostas/{reply}', [ReplyController::class, 'update'])->name('topics.replies.update');
            Route::delete('topicos/{topic}/respostas/{reply}', [ReplyController::class, 'destroy'])->name('topics.replies.destroy');
            Route::post('topicos/{topic}/respostas/{reply}/denuncia', [ReportController::class, 'reply'])->middleware('throttle:10,1')->name('topics.replies.report');
        });
    });

    Route::get('notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notificacoes/lidas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('notificacoes/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::get('videos/{video}/{playlist}.m3u8', [VideoPlaylistController::class, 'show'])
        ->where('playlist', '[a-z0-9]+')
        ->name('videos.playlist');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
