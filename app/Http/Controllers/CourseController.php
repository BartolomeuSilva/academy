<?php

namespace App\Http\Controllers;

use App\Actions\BuildCourseOutline;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * List the published courses with the student's progress.
     */
    public function index(Request $request): Response
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
            ->orderBy('position')
            ->get();

        $featured = $courses->first(fn (Course $course) => $course->featured_at !== null && $course->banner_path !== null);

        return Inertia::render('courses/Index', [
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
            ]),
        ]);
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
