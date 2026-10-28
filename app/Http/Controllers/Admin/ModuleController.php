<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModuleRequest;
use App\Jobs\DeleteStoragePaths;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ModuleController extends Controller
{
    /**
     * Add a module to the end of the course.
     */
    public function store(ModuleRequest $request, Course $course): RedirectResponse
    {
        $module = new Module($request->validated());
        $module->position = (int) $course->modules()->max('position') + 1;
        $course->modules()->save($module);

        return back();
    }

    /**
     * Rename the module.
     */
    public function update(ModuleRequest $request, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        return back();
    }

    /**
     * Delete the module and its lessons.
     */
    public function destroy(Module $module): RedirectResponse
    {
        DB::transaction(function () use ($module) {
            DeleteStoragePaths::dispatch($module->storagePaths());

            $module->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Módulo excluído.']);

        return back();
    }
}
