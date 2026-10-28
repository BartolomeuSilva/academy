<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;

class ModuleOrderController extends Controller
{
    /**
     * Persist the order of the course modules.
     */
    public function update(ReorderRequest $request, Course $course, ReorderRecords $reorderRecords): RedirectResponse
    {
        $reorderRecords->handle(Module::query()->whereBelongsTo($course), $request->orderedIds());

        return back();
    }
}
