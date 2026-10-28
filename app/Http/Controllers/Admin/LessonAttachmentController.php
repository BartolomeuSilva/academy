<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttachmentKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonAttachmentsRequest;
use App\Http\Requests\Admin\UpdateLessonAttachmentRequest;
use App\Jobs\DeleteStoragePaths;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use RuntimeException;

class LessonAttachmentController extends Controller
{
    /**
     * Upload one or more files to the end of the lesson attachments.
     */
    public function store(StoreLessonAttachmentsRequest $request, Lesson $lesson): RedirectResponse
    {
        $position = (int) $lesson->attachments()->max('position');

        /** @var list<UploadedFile> $files */
        $files = $request->file('files');

        foreach ($files as $file) {
            $mimeType = (string) $file->getMimeType();

            $attachment = new LessonAttachment(['original_name' => $file->getClientOriginalName()]);
            $attachment->kind = AttachmentKind::fromMimeType($mimeType);
            $attachment->mime_type = $mimeType;
            $attachment->size = (int) $file->getSize();
            $attachment->path = $file->store('attachments')
                ?: throw new RuntimeException('Não foi possível salvar o anexo.');
            $attachment->position = ++$position;

            $lesson->attachments()->save($attachment);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => count($files) > 1 ? 'Anexos enviados.' : 'Anexo enviado.']);

        return back();
    }

    /**
     * Rename the attachment.
     */
    public function update(UpdateLessonAttachmentRequest $request, LessonAttachment $attachment): RedirectResponse
    {
        $attachment->update($request->validated());

        return back();
    }

    /**
     * Delete the attachment and its file.
     */
    public function destroy(LessonAttachment $attachment): RedirectResponse
    {
        $attachment->delete();

        DeleteStoragePaths::dispatch([$attachment->path]);

        return back();
    }
}
