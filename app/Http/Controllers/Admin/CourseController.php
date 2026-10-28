<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Jobs\DeleteStoragePaths;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * List every course in catalog order.
     */
    public function index(): Response
    {
        $courses = Course::query()
            ->withCount(['modules', 'lessons'])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'summary' => $course->summary,
                'status' => $course->status,
                'cover_url' => $course->coverUrl(),
                'modules_count' => $course->modules_count,
                'lessons_count' => $course->lessons_count,
            ]);

        return Inertia::render('admin/courses/Index', [
            'courses' => $courses,
        ]);
    }

    /**
     * Show the form to create a course.
     */
    public function create(): Response
    {
        return Inertia::render('admin/courses/Create');
    }

    /**
     * Store a new draft course at the end of the catalog.
     */
    public function store(StoreCourseRequest $request, GenerateUniqueSlug $generateUniqueSlug): RedirectResponse
    {
        $course = new Course($request->validated());
        $course->slug = $generateUniqueSlug->handle(Course::query(), $course->title);
        $course->status = PublicationStatus::Draft;
        $course->position = (int) Course::query()->max('position') + 1;
        $course->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso criado. Agora adicione módulos e aulas.']);

        return to_route('admin.courses.edit', $course);
    }

    /**
     * Show the course details and its content structure.
     */
    public function edit(Course $course): Response
    {
        $course->load(['modules.lessons' => fn ($query) => $query->withCount('attachments')]);

        return Inertia::render('admin/courses/Edit', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'summary' => $course->summary,
                'description' => $course->description,
                'status' => $course->status,
                'cover_url' => $course->coverUrl(),
            ],
            'modules' => $course->modules->map(fn (Module $module) => [
                'id' => $module->id,
                'title' => $module->title,
                'lessons' => $module->lessons->map(fn (Lesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'status' => $lesson->status,
                    'attachments_count' => $lesson->attachments_count,
                ]),
            ]),
        ]);
    }

    /**
     * Update the course details and publication status.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->fill($request->safe()->except('status'));
        $course->status = $request->enum('status', PublicationStatus::class) ?? $course->status;

        if ($course->status === PublicationStatus::Published && $course->published_at === null) {
            $course->published_at = now();
        }

        $course->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso atualizado.']);

        return to_route('admin.courses.edit', $course);
    }

    /**
     * Delete the course with all of its content and files.
     */
    public function destroy(Course $course): RedirectResponse
    {
        DB::transaction(function () use ($course) {
            DeleteStoragePaths::dispatch($course->storagePaths());

            $course->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso excluído.']);

        return to_route('admin.courses.index');
    }
}
