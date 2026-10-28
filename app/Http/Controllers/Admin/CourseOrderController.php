<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;

class CourseOrderController extends Controller
{
    /**
     * Persist the catalog order of the courses.
     */
    public function update(ReorderRequest $request, ReorderRecords $reorderRecords): RedirectResponse
    {
        $reorderRecords->handle(Course::query(), $request->orderedIds());

        return back();
    }
}
