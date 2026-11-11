<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLessonRatingRequest;
use App\Models\Lesson;
use App\Models\LessonRating;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LessonRatingController extends Controller
{
    /**
     * Save the student's rating of the lesson, replacing an earlier one.
     */
    public function update(UpdateLessonRatingRequest $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('view', $lesson), 404);

        LessonRating::query()->upsert(
            [[
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'stars' => $request->integer('stars'),
                'feedback' => $request->string('feedback')->trim()->toString() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            uniqueBy: ['user_id', 'lesson_id'],
            update: ['stars', 'feedback', 'updated_at'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Obrigado pela avaliação!']);

        return back();
    }
}
