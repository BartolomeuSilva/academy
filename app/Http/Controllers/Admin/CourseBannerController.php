<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCourseBannerRequest;
use App\Jobs\DeleteStoragePaths;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use RuntimeException;

class CourseBannerController extends Controller
{
    /**
     * Upload a new banner, replacing the current one.
     */
    public function store(UpdateCourseBannerRequest $request, Course $course): RedirectResponse
    {
        $previousPath = $course->banner_path;

        $course->banner_path = $request->file('banner')->store('banners')
            ?: throw new RuntimeException('Não foi possível salvar o banner.');
        $course->save();

        if ($previousPath) {
            DeleteStoragePaths::dispatch([$previousPath]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Banner atualizado.']);

        return back();
    }

    /**
     * Remove the banner; a course without one can not stay featured.
     */
    public function destroy(Course $course): RedirectResponse
    {
        if ($course->banner_path) {
            DeleteStoragePaths::dispatch([$course->banner_path]);

            $course->banner_path = null;
            $course->featured_at = null;
            $course->save();
        }

        return back();
    }
}
