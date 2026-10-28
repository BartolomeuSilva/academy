<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Http\Requests\Admin\UpdateLessonRequest;
use App\Jobs\DeleteStoragePaths;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Add a draft lesson to the end of the module.
     */
    public function store(StoreLessonRequest $request, Module $module, GenerateUniqueSlug $generateUniqueSlug): RedirectResponse
    {
        $lesson = new Lesson($request->validated());
        $lesson->course_id = $module->course_id;
        $lesson->slug = $generateUniqueSlug->handle(Lesson::query()->where('course_id', $module->course_id), $lesson->title);
        $lesson->status = PublicationStatus::Draft;
        $lesson->position = (int) $module->lessons()->max('position') + 1;
        $module->lessons()->save($lesson);

        return back();
    }

    /**
     * Show the lesson details and attachments.
     */
    public function edit(Lesson $lesson): Response
    {
        $lesson->load(['course', 'module', 'attachments']);

        return Inertia::render('admin/lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'description' => $lesson->description,
                'status' => $lesson->status,
                'module' => ['id' => $lesson->module->id, 'title' => $lesson->module->title],
                'course' => ['id' => $lesson->course->id, 'title' => $lesson->course->title],
            ],
            'attachments' => $lesson->attachments->map(fn (LessonAttachment $attachment) => [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'url' => $attachment->temporaryUrl(),
            ]),
        ]);
    }

    /**
     * Update the lesson details.
     */
    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula atualizada.']);

        return to_route('admin.lessons.edit', $lesson);
    }

    /**
     * Delete the lesson and its files.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        DB::transaction(function () use ($lesson) {
            DeleteStoragePaths::dispatch($lesson->storagePaths());

            $lesson->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aula excluída.']);

        return to_route('admin.courses.edit', $lesson->course_id);
    }
}
