<?php

namespace App\Http\Controllers;

use App\Actions\BuildCourseOutline;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
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
            ->addSelect(['duration_seconds' => Video::query()
                ->selectRaw('coalesce(sum(videos.duration_seconds), 0)')
                ->join('lessons', 'lessons.video_id', '=', 'videos.id')
                ->whereColumn('lessons.course_id', 'courses.id')
                ->where('lessons.status', 'published'),
            ])
            ->orderBy('position')
            ->get();

        return Inertia::render('courses/Index', [
            'courses' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $course->summary,
                'cover_url' => $course->coverUrl(),
                'lessons_count' => $course->lessons_count,
                'completed_lessons_count' => $course->completed_lessons_count,
                'duration_seconds' => (int) $course->duration_seconds,
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
     * Show the course with its modules, lessons and the lesson to continue from.
     */
    public function show(Request $request, Course $course, BuildCourseOutline $buildCourseOutline): Response
    {
        abort_unless($request->user()->can('view', $course), 404);

        $outline = $buildCourseOutline->handle($course, $request->user());

        return Inertia::render('courses/Show', [
            'course' => [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
                'summary' => $course->summary,
                'description_html' => $course->descriptionHtml(),
                'cover_url' => $course->coverUrl(),
                'status' => $course->status,
            ],
            'modules' => $outline['modules'],
            'progress' => [
                'completed' => $outline['completed_count'],
                'total' => count($outline['lessons']),
                'duration_seconds' => array_sum(array_column($outline['lessons'], 'duration_seconds')),
            ],
            'resume' => $outline['resume'],
        ]);
    }
}
