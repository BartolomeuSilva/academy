<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @phpstan-type OutlineLesson array{id: int, slug: string, title: string, duration_seconds: int|null, completed: bool}
 * @phpstan-type OutlineModule array{id: int, title: string, lessons: list<OutlineLesson>}
 * @phpstan-type Outline array{modules: list<OutlineModule>, lessons: list<OutlineLesson>, completed_count: int, resume: array{lesson: OutlineLesson, started: bool}|null}
 */
class BuildCourseOutline
{
    /**
     * Build the modules and lessons the user may open, with their progress and the lesson to resume.
     *
     * @return Outline
     */
    public function handle(Course $course, User $user): array
    {
        $modules = $course->modules()
            ->with(['lessons' => fn (HasMany $lessons) => $lessons
                ->visibleTo($user)
                ->with('video:id,duration_seconds')
                ->select(['id', 'module_id', 'video_id', 'slug', 'title', 'position']),
            ])
            ->get(['id', 'course_id', 'title', 'position'])
            ->filter(fn (Module $module) => $module->lessons->isNotEmpty());

        $progress = LessonProgress::query()
            ->whereBelongsTo($user)
            ->whereIn('lesson_id', Lesson::query()->whereBelongsTo($course)->select('id'))
            ->get(['lesson_id', 'completed_at', 'updated_at'])
            ->keyBy('lesson_id');

        $outlineModules = array_values($modules->map(fn (Module $module) => [
            'id' => $module->id,
            'title' => $module->title,
            'lessons' => array_values($module->lessons->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'duration_seconds' => $lesson->video?->duration_seconds,
                'completed' => (bool) $progress->get($lesson->id)?->isCompleted(),
            ])->all()),
        ])->all());

        $lessons = array_merge([], ...array_column($outlineModules, 'lessons'));

        return [
            'modules' => $outlineModules,
            'lessons' => $lessons,
            'completed_count' => count(array_filter($lessons, fn (array $lesson) => $lesson['completed'])),
            'resume' => $this->resume($lessons, $progress->sortByDesc('updated_at')->first()),
        ];
    }

    /**
     * Pick the lesson to continue: the last one watched, or the next unfinished one after it.
     *
     * @param  list<OutlineLesson>  $lessons
     * @return array{lesson: OutlineLesson, started: bool}|null
     */
    private function resume(array $lessons, ?LessonProgress $lastWatched): ?array
    {
        if ($lessons === []) {
            return null;
        }

        $lastIndex = $lastWatched ? array_search($lastWatched->lesson_id, array_column($lessons, 'id'), true) : false;

        if ($lastIndex === false) {
            return ['lesson' => $lessons[0], 'started' => false];
        }

        if (! $lessons[$lastIndex]['completed']) {
            return ['lesson' => $lessons[$lastIndex], 'started' => true];
        }

        $remaining = [...array_slice($lessons, $lastIndex + 1), ...array_slice($lessons, 0, $lastIndex)];

        foreach ($remaining as $lesson) {
            if (! $lesson['completed']) {
                return ['lesson' => $lesson, 'started' => true];
            }
        }

        return ['lesson' => $lessons[0], 'started' => true];
    }
}
