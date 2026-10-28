<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReorderRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRequest;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;

class LessonAttachmentOrderController extends Controller
{
    /**
     * Persist the order of the lesson attachments.
     */
    public function update(ReorderRequest $request, Lesson $lesson, ReorderRecords $reorderRecords): RedirectResponse
    {
        $reorderRecords->handle(LessonAttachment::query()->whereBelongsTo($lesson), $request->orderedIds());

        return back();
    }
}
