<?php

use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseCoverController;
use App\Http\Controllers\Admin\CourseOrderController;
use App\Http\Controllers\Admin\LessonAttachmentController;
use App\Http\Controllers\Admin\LessonAttachmentOrderController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\LessonOrderController;
use App\Http\Controllers\Admin\LessonVideoController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ModuleOrderController;
use App\Http\Controllers\Admin\UserBlockController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::put('cursos/ordem', [CourseOrderController::class, 'update'])->name('courses.order');
        Route::get('cursos', [CourseController::class, 'index'])->name('courses.index');
        Route::get('cursos/criar', [CourseController::class, 'create'])->name('courses.create');
        Route::post('cursos', [CourseController::class, 'store'])->name('courses.store');
        Route::get('cursos/{course}/editar', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('cursos/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('cursos/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

        Route::post('cursos/{course}/capa', [CourseCoverController::class, 'store'])->name('courses.cover.store');
        Route::delete('cursos/{course}/capa', [CourseCoverController::class, 'destroy'])->name('courses.cover.destroy');

        Route::post('cursos/{course}/modulos', [ModuleController::class, 'store'])->name('courses.modules.store');
        Route::put('cursos/{course}/modulos/ordem', [ModuleOrderController::class, 'update'])->name('courses.modules.order');
        Route::put('cursos/{course}/aulas/ordem', [LessonOrderController::class, 'update'])->name('courses.lessons.order');

        Route::put('modulos/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('modulos/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
        Route::post('modulos/{module}/aulas', [LessonController::class, 'store'])->name('modules.lessons.store');

        Route::get('aulas/{lesson}/editar', [LessonController::class, 'edit'])->name('lessons.edit');
        Route::put('aulas/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
        Route::delete('aulas/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

        Route::post('aulas/{lesson}/video', [LessonVideoController::class, 'store'])->name('lessons.video.store');
        Route::post('videos/{video}/partes', [LessonVideoController::class, 'parts'])->name('videos.parts');
        Route::post('videos/{video}/concluir', [LessonVideoController::class, 'complete'])->name('videos.complete');
        Route::delete('videos/{video}', [LessonVideoController::class, 'destroy'])->name('videos.destroy');

        Route::post('aulas/{lesson}/anexos', [LessonAttachmentController::class, 'store'])->name('lessons.attachments.store');
        Route::put('aulas/{lesson}/anexos/ordem', [LessonAttachmentOrderController::class, 'update'])->name('lessons.attachments.order');
        Route::put('anexos/{attachment}', [LessonAttachmentController::class, 'update'])->name('attachments.update');
        Route::delete('anexos/{attachment}', [LessonAttachmentController::class, 'destroy'])->name('attachments.destroy');

        Route::get('comentarios', [CommentController::class, 'index'])->name('comments.index');
        Route::delete('comentarios/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

        Route::get('usuarios', [UserController::class, 'index'])->name('users.index');
        Route::put('usuarios/{user}/papel', [UserRoleController::class, 'update'])->name('users.role.update');
        Route::post('usuarios/{user}/bloqueio', [UserBlockController::class, 'store'])->name('users.block.store');
        Route::delete('usuarios/{user}/bloqueio', [UserBlockController::class, 'destroy'])->name('users.block.destroy');
    });
