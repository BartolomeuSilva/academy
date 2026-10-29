<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CourseFeatureController extends Controller
{
    /**
     * Feature the course at the top of the catalog, replacing the current one.
     */
    public function store(Course $course): RedirectResponse
    {
        if ($course->banner_path === null) {
            throw ValidationException::withMessages([
                'banner' => 'Envie o banner antes de destacar o curso.',
            ]);
        }

        DB::transaction(function () use ($course): void {
            Course::query()->whereKeyNot($course->id)->whereNotNull('featured_at')->update(['featured_at' => null]);

            $course->featured_at = now();
            $course->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso em destaque na vitrine.']);

        return back();
    }

    /**
     * Stop featuring the course.
     */
    public function destroy(Course $course): RedirectResponse
    {
        $course->featured_at = null;
        $course->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Curso removido do destaque.']);

        return back();
    }
}
