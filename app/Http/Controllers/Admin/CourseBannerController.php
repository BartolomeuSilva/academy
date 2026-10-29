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
     * Upload the desktop or phone banner, replacing the current one.
     */
    public function store(UpdateCourseBannerRequest $request, Course $course, string $format): RedirectResponse
    {
        $column = $this->column($format);
        $previousPath = $course->{$column};

        $course->{$column} = $request->file('banner')->store('banners')
            ?: throw new RuntimeException('Não foi possível salvar o banner.');
        $course->save();

        if ($previousPath) {
            DeleteStoragePaths::dispatch([$previousPath]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Banner atualizado.']);

        return back();
    }

    /**
     * Remove a banner; without the desktop one the course can not stay featured.
     */
    public function destroy(Course $course, string $format): RedirectResponse
    {
        $column = $this->column($format);

        if ($course->{$column}) {
            DeleteStoragePaths::dispatch([$course->{$column}]);

            $course->{$column} = null;

            if ($column === 'banner_path') {
                $course->featured_at = null;
            }

            $course->save();
        }

        return back();
    }

    /**
     * @return 'banner_path'|'banner_mobile_path'
     */
    private function column(string $format): string
    {
        return $format === 'celular' ? 'banner_mobile_path' : 'banner_path';
    }
}
