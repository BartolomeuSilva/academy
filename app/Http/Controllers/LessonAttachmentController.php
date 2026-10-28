<?php

namespace App\Http\Controllers;

use App\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonAttachmentController extends Controller
{
    /**
     * Redirect to a short-lived URL that opens or downloads the attachment.
     */
    public function show(Request $request, LessonAttachment $attachment): RedirectResponse
    {
        abort_unless($request->user()->can('view', $attachment->lesson), 404);

        return redirect()->away($attachment->temporaryUrl(download: $request->boolean('download'), minutes: 5));
    }
}
