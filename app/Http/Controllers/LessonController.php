<?php

namespace App\Http\Controllers;

use App\Actions\BuildCourseOutline;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Saved positions this close to the end restart the video instead of resuming it.
     */
    private const int RESTART_WITHIN_SECONDS = 15;

    /**
     * Show the lesson player, materials and the course outline.
     */
    public function show(Request $request, Course $course, Lesson $lesson, BuildCourseOutline $buildCourseOutline): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $lesson), 404);

        $lesson->load(['video', 'attachments']);
        $progress = $lesson->progress()->whereBelongsTo($user)->first();
        $outline = $buildCourseOutline->handle($course, $user);

        $position = array_search($lesson->id, array_column($outline['lessons'], 'id'), true);
        $neighbour = fn (int $offset) => $position === false ? null : ($outline['lessons'][$position + $offset] ?? null);

        return Inertia::render('lessons/Show', [
            'course' => [
                'slug' => $course->slug,
                'title' => $course->title,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'description_html' => $lesson->descriptionHtml(),
                'status' => $lesson->status,
            ],
            'video' => $lesson->video ? [
                'playlist_url' => route('videos.playlist', [$lesson->video, 'master']),
                'poster_url' => $lesson->video->posterUrl(),
                'duration_seconds' => $lesson->video->duration_seconds,
            ] : null,
            'progress' => [
                'completed' => (bool) $progress?->isCompleted(),
                'start_at_seconds' => $this->startAt($progress, $lesson->video?->duration_seconds),
            ],
            'attachments' => $lesson->attachments->map(fn (LessonAttachment $attachment) => [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'url' => route('attachments.show', $attachment),
                'download_url' => route('attachments.show', [$attachment, 'download' => 1]),
            ]),
            'modules' => $outline['modules'],
            'previous_lesson' => $neighbour(-1),
            'next_lesson' => $neighbour(1),
        ]);
    }

    private function startAt(?LessonProgress $progress, ?int $durationSeconds): int
    {
        $position = $progress->last_position_seconds ?? 0;

        if ($durationSeconds !== null && $position >= $durationSeconds - self::RESTART_WITHIN_SECONDS) {
            return 0;
        }

        return $position;
    }
}
