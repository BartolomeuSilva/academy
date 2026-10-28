<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLessonProgressRequest;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LessonProgressController extends Controller
{
    /**
     * Save the playback position and/or the manual completion of the lesson.
     */
    public function update(UpdateLessonProgressRequest $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->can('view', $lesson), 404);

        $progress = DB::transaction(function () use ($request, $lesson, $user) {
            LessonProgress::query()->insertOrIgnore([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'last_position_seconds' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $progress = LessonProgress::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($lesson)
                ->lockForUpdate()
                ->sole();

            if ($request->has('position_seconds')) {
                $progress->recordPosition($request->integer('position_seconds'), $lesson->video?->duration_seconds);
            }

            if ($request->has('completed')) {
                $progress->completed_at = $request->boolean('completed') ? ($progress->completed_at ?? now()) : null;
            }

            $progress->touch();

            return $progress;
        });

        return response()->json([
            'last_position_seconds' => $progress->last_position_seconds,
            'completed' => $progress->isCompleted(),
        ]);
    }
}
