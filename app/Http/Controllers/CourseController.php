<?php

namespace App\Http\Controllers;

use App\Actions\BuildCourseOutline;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-import-type Outline from BuildCourseOutline
 */
class CourseController extends Controller
{
    /**
     * List the published courses with the student's progress.
     */
    public function index(Request $request, BuildCourseOutline $buildCourseOutline): Response
    {
        $user = $request->user();

        $courses = Course::query()
            ->visible()
            ->withCount([
                'lessons' => $this->visibleLessons(...),
                'lessons as completed_lessons_count' => fn (Builder $lessons) => $this->visibleLessons($lessons)
                    ->visible()
                    ->whereHas('progress', fn (Builder $progress) => $progress->whereBelongsTo($user)->whereNotNull('completed_at')),
            ])
            ->withExists([
                'lessons as started' => fn (Builder $lessons) => $this->visibleLessons($lessons)
                    ->whereHas('progress', fn (Builder $progress) => $progress->whereBelongsTo($user)),
            ])
            ->addSelect(['duration_seconds' => Video::query()
                ->selectRaw('coalesce(sum(videos.duration_seconds), 0)')
                ->join('lessons', 'lessons.video_id', '=', 'videos.id')
                ->whereColumn('lessons.course_id', 'courses.id')
                ->where('lessons.status', 'published'),
            ])
            ->addSelect(['last_watched_at' => LessonProgress::query()
                ->selectRaw('max(lesson_progress.updated_at)')
                ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
                ->whereColumn('lessons.course_id', 'courses.id')
                ->whereBelongsTo($user),
            ])
            ->orderBy('position')
            ->get();

        $featured = $courses->first(fn (Course $course) => $course->featured_at !== null && $course->banner_path !== null);

        $current = $courses
            ->filter(fn (Course $course) => $course->started && $course->completed_lessons_count < $course->lessons_count)
            ->sortByDesc('last_watched_at')
            ->first();

        return Inertia::render('courses/Index', [
            'next_lesson' => $current ? $this->nextLesson($current, $buildCourseOutline->handle($current, $user)) : null,
            'featured' => $featured ? [
                'id' => $featured->id,
                'banner_url' => $featured->bannerUrl(),
                'banner_mobile_url' => $featured->bannerMobileUrl(),
            ] : null,
            'courses' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $course->summary,
                'category' => $course->category,
                'cover_url' => $course->coverUrl(),
                'lessons_count' => $course->lessons_count,
                'completed_lessons_count' => $course->completed_lessons_count,
                'duration_seconds' => (int) $course->duration_seconds,
                'started' => (bool) $course->started,
                'last_watched_at' => $course->last_watched_at,
            ]),
        ]);
    }

    /**
     * Describe the lesson the student should watch next in the course they watched last.
     *
     * @param  Outline  $outline
     * @return array{course: array{slug: string, title: string}, lesson: array{slug: string, title: string, number: int, duration_seconds: int|null}, started: bool, remaining_lessons: int, remaining_seconds: int}|null
     */
    private function nextLesson(Course $course, array $outline): ?array
    {
        if ($outline['resume'] === null) {
            return null;
        }

        $lesson = $outline['resume']['lesson'];
        $remaining = array_filter($outline['lessons'], fn (array $item) => ! $item['completed']);

        return [
            'course' => ['slug' => $course->slug, 'title' => $course->title],
            'lesson' => [
                'slug' => $lesson['slug'],
                'title' => $lesson['title'],
                'number' => array_search($lesson['id'], array_column($outline['lessons'], 'id'), true) + 1,
                'duration_seconds' => $lesson['duration_seconds'],
            ],
            'started' => $outline['resume']['started'],
            'remaining_lessons' => count($remaining),
            'remaining_seconds' => array_sum(array_column($remaining, 'duration_seconds')),
        ];
    }

    /**
     * @param  Builder<Lesson>  $lessons
     * @return Builder<Lesson>
     */
    private function visibleLessons(Builder $lessons): Builder
    {
        return $lessons->visible();
    }

    /**
     * Open the course at the lesson the student should watch next.
     */
    public function show(Request $request, Course $course, BuildCourseOutline $buildCourseOutline): RedirectResponse
    {
        abort_unless($request->user()->can('view', $course), 404);

        $resume = $buildCourseOutline->handle($course, $request->user())['resume'];

        if ($resume === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Este curso ainda não tem aulas.']);

            return to_route('admin.courses.edit', $course);
        }

        return to_route('lessons.show', [$course, $resume['lesson']['slug']]);
    }
}
