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
use App\Models\LessonRating;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Written feedback entries shown on the lesson editor.
     */
    public const int FEEDBACK_SHOWN = 20;

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
        $lesson->load(['course', 'module', 'attachments', 'video']);

        $pendingVideo = $lesson->videos()
            ->whereKeyNot($lesson->video_id ?? 0)
            ->latest('id')
            ->first();

        return Inertia::render('admin/lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'description' => $lesson->description,
                'status' => $lesson->status,
                'module' => ['id' => $lesson->module->id, 'title' => $lesson->module->title],
                'course' => ['id' => $lesson->course->id, 'slug' => $lesson->course->slug, 'title' => $lesson->course->title],
            ],
            'video' => $lesson->video ? [
                'id' => $lesson->video->id,
                'original_name' => $lesson->video->original_name,
                'duration_seconds' => $lesson->video->duration_seconds,
                'renditions' => array_column($lesson->video->renditions ?? [], 'name'),
                'poster_url' => $lesson->video->posterUrl(),
                'playlist_url' => route('videos.playlist', [$lesson->video, 'master']),
                'processed_at' => $lesson->video->processed_at?->toIso8601String(),
            ] : null,
            'pending_video' => $pendingVideo ? [
                'id' => $pendingVideo->id,
                'status' => $pendingVideo->status,
                'original_name' => $pendingVideo->original_name,
                'error_message' => $pendingVideo->error_message,
            ] : null,
            'attachments' => $lesson->attachments->map(fn (LessonAttachment $attachment) => [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'url' => $attachment->temporaryUrl(),
            ]),
            'ratings' => $this->ratings($lesson),
        ]);
    }

    /**
     * Summarise the lesson's ratings with the latest written feedback.
     *
     * @return array{average: float|null, count: int, distribution: array<int, int>, feedback: list<array{id: int, stars: int, feedback: string, author: string, updated_at: string|null}>}
     */
    private function ratings(Lesson $lesson): array
    {
        $counts = $lesson->ratings()->toBase()
            ->selectRaw('stars, count(*) as total')
            ->groupBy('stars')
            ->pluck('total', 'stars');
        $count = (int) $counts->sum();

        return [
            'average' => $count > 0 ? round($counts->map(fn ($total, $stars) => $stars * $total)->sum() / $count, 1) : null,
            'count' => $count,
            'distribution' => collect(range(5, 1))->mapWithKeys(fn (int $stars) => [$stars => (int) ($counts[$stars] ?? 0)])->all(),
            'feedback' => array_values($lesson->ratings()
                ->whereNotNull('feedback')
                ->with('user:id,name')
                ->latest('updated_at')
                ->limit(self::FEEDBACK_SHOWN)
                ->get()
                ->map(fn (LessonRating $rating) => [
                    'id' => $rating->id,
                    'stars' => $rating->stars,
                    'feedback' => (string) $rating->feedback,
                    'author' => $rating->user->name,
                    'updated_at' => $rating->updated_at?->toIso8601String(),
                ])
                ->all()),
        ];
    }

    /**
     * Update the lesson details.
     */
    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->fill($request->safe()->except('status'));
        $lesson->status = $request->enum('status', PublicationStatus::class) ?? $lesson->status;
        $lesson->save();

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
