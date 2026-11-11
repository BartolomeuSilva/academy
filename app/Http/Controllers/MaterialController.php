<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public const int PER_PAGE = 24;

    /**
     * List the materials of every lesson in the catalog, optionally searching by name or filtering by course.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('busca')->trim()->limit(100, '')->toString();
        $courseSlug = $request->string('curso')->trim()->toString();

        $materials = LessonAttachment::query()
            ->select('lesson_attachments.*')
            ->join('lessons', 'lessons.id', '=', 'lesson_attachments.lesson_id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->join('courses', 'courses.id', '=', 'lessons.course_id')
            ->whereHas('lesson', fn (Builder $lessons) => $lessons
                ->visible()
                ->whereHas('course', fn (Builder $courses) => $courses->visible()))
            ->when($search !== '', fn (Builder $query) => $query->whereLike('lesson_attachments.original_name', "%{$search}%"))
            ->when($courseSlug !== '', fn (Builder $query) => $query->where('courses.slug', $courseSlug))
            ->with(['lesson:id,course_id,slug,title', 'lesson.course:id,slug,title'])
            ->orderBy('courses.position')
            ->orderBy('modules.position')
            ->orderBy('lessons.position')
            ->orderBy('lesson_attachments.position')
            ->orderBy('lesson_attachments.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (LessonAttachment $attachment) => [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
                'url' => route('attachments.show', $attachment),
                'download_url' => route('attachments.show', [$attachment, 'download' => 1]),
                'course' => ['slug' => $attachment->lesson->course->slug, 'title' => $attachment->lesson->course->title],
                'lesson' => ['slug' => $attachment->lesson->slug, 'title' => $attachment->lesson->title],
            ]);

        return Inertia::render('materials/Index', [
            'materials' => $materials,
            'courses' => Course::query()
                ->visible()
                ->whereHas('lessons', fn (Builder $lessons) => $lessons->visible()->has('attachments'))
                ->orderBy('position')
                ->get(['id', 'slug', 'title']),
            'filters' => ['search' => $search, 'course' => $courseSlug],
        ]);
    }
}
