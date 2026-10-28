<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCourseCoverRequest;
use App\Jobs\DeleteStoragePaths;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use RuntimeException;

class CourseCoverController extends Controller
{
    /**
     * Upload a new cover, replacing the current one.
     */
    public function store(UpdateCourseCoverRequest $request, Course $course): RedirectResponse
    {
        $previousPath = $course->cover_path;

        $course->cover_path = $request->file('cover')->store('covers')
            ?: throw new RuntimeException('Não foi possível salvar a capa.');
        $course->save();

        if ($previousPath) {
            DeleteStoragePaths::dispatch([$previousPath]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Capa atualizada.']);

        return back();
    }

    /**
     * Remove the course cover.
     */
    public function destroy(Course $course): RedirectResponse
    {
        if ($course->cover_path) {
            DeleteStoragePaths::dispatch([$course->cover_path]);

            $course->cover_path = null;
            $course->save();
        }

        return back();
    }
}
