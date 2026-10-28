<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderLessonsRequest;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonOrderController extends Controller
{
    /**
     * Persist the order of the lessons, including moves between modules of the course.
     */
    public function update(ReorderLessonsRequest $request, Course $course): RedirectResponse
    {
        $lessonIdsByModule = $request->lessonIdsByModule();

        $this->ensureMatchesCourse($course, $lessonIdsByModule);

        DB::transaction(function () use ($lessonIdsByModule) {
            foreach ($lessonIdsByModule as $moduleId => $lessonIds) {
                foreach ($lessonIds as $position => $lessonId) {
                    Lesson::query()->whereKey($lessonId)->update([
                        'module_id' => $moduleId,
                        'position' => $position,
                    ]);
                }
            }
        });

        return back();
    }

    /**
     * Reject payloads that omit or add modules or lessons of the course.
     *
     * @param  array<int, list<int>>  $lessonIdsByModule
     */
    private function ensureMatchesCourse(Course $course, array $lessonIdsByModule): void
    {
        $moduleIds = $course->modules()->pluck('id')->sort()->values()->all();
        $lessonIds = $course->lessons()->pluck('id')->sort()->values()->all();

        $givenModuleIds = collect(array_keys($lessonIdsByModule))->sort()->values()->all();
        $givenLessonIds = collect($lessonIdsByModule)->flatten()->sort()->values()->all();

        if ($moduleIds !== $givenModuleIds || $lessonIds !== $givenLessonIds) {
            throw ValidationException::withMessages([
                'modules' => 'A lista enviada não corresponde aos itens atuais. Recarregue a página e tente novamente.',
            ]);
        }
    }
}
