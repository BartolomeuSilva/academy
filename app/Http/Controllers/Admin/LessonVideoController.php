<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompleteVideoUploadRequest;
use App\Http\Requests\Admin\SignVideoPartsRequest;
use App\Http\Requests\Admin\StoreLessonVideoRequest;
use App\Jobs\DeleteStoragePaths;
use App\Jobs\EncodeVideo;
use App\Models\Lesson;
use App\Models\Video;
use App\Services\Video\S3MultipartUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class LessonVideoController extends Controller
{
    public function __construct(private S3MultipartUploader $uploader) {}

    /**
     * Start a direct-to-storage multipart upload for a new video of the lesson,
     * discarding any earlier upload that never became the lesson's video.
     */
    public function store(StoreLessonVideoRequest $request, Lesson $lesson): JsonResponse
    {
        $lesson->videos()
            ->whereKeyNot($lesson->video_id ?? 0)
            ->get()
            ->each(fn (Video $video) => $this->discard($video));

        $uuid = (string) Str::uuid();
        $extension = Str::lower(pathinfo($request->string('filename')->toString(), PATHINFO_EXTENSION));
        $originalPath = "videos/{$uuid}/original.{$extension}";

        $video = new Video;
        $video->uuid = $uuid;
        $video->status = VideoStatus::Uploading;
        $video->original_name = $request->string('filename')->toString();
        $video->original_size = $request->integer('size');
        $video->original_path = $originalPath;
        $video->upload_id = $this->uploader->start($originalPath, $request->string('content_type', 'video/mp4')->toString());
        $lesson->videos()->save($video);

        return response()->json([
            'video_id' => $video->id,
            'part_size' => $this->partSize(),
            'part_count' => $this->partCount($video),
        ], 201);
    }

    /**
     * Sign upload URLs for the requested parts.
     */
    public function parts(SignVideoPartsRequest $request, Video $video): JsonResponse
    {
        $this->ensureUploading($video);

        $partCount = $this->partCount($video);
        $partNumbers = $request->partNumbers();

        if (collect($partNumbers)->contains(fn (int $partNumber) => $partNumber > $partCount)) {
            throw ValidationException::withMessages(['part_numbers' => "O vídeo tem apenas {$partCount} partes."]);
        }

        $expiresAt = now()->addHour();

        return response()->json([
            'urls' => collect($partNumbers)->mapWithKeys(fn (int $partNumber) => [
                $partNumber => $this->uploader->partUrl((string) $video->original_path, (string) $video->upload_id, $partNumber, $expiresAt),
            ]),
        ]);
    }

    /**
     * Assemble the parts and queue the encoding.
     */
    public function complete(CompleteVideoUploadRequest $request, Video $video): JsonResponse
    {
        $this->ensureUploading($video);

        $parts = $request->s3Parts();
        $expectedPartNumbers = range(1, $this->partCount($video));

        if (array_column($parts, 'PartNumber') !== $expectedPartNumbers) {
            throw ValidationException::withMessages(['parts' => 'O envio está incompleto. Envie o vídeo novamente.']);
        }

        $this->uploader->complete((string) $video->original_path, (string) $video->upload_id, $parts);

        if ($this->storedSize($video) > (int) config('academy.video_max_upload_bytes')) {
            $this->discard($video->fresh() ?? $video);

            throw ValidationException::withMessages(['parts' => 'O vídeo deve ter no máximo 2 GB.']);
        }

        DB::transaction(function () use ($video) {
            $video->forceFill(['status' => VideoStatus::Queued, 'upload_id' => null])->save();

            EncodeVideo::dispatch($video)->afterCommit();
        });

        return response()->json(['status' => VideoStatus::Queued], 202);
    }

    /**
     * Cancel an upload or remove a failed video. The lesson's current video can only be replaced.
     */
    public function destroy(Video $video): RedirectResponse
    {
        if ($video->lesson->video_id === $video->id) {
            throw ValidationException::withMessages(['video' => 'Este é o vídeo atual da aula. Envie outro vídeo para substituí-lo.']);
        }

        $this->discard($video);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Envio cancelado.']);

        return back();
    }

    /**
     * Abort a pending upload, delete the video's files and its record.
     */
    private function discard(Video $video): void
    {
        if ($video->status === VideoStatus::Uploading && $video->upload_id && $video->original_path) {
            try {
                $this->uploader->abort($video->original_path, $video->upload_id);
            } catch (Throwable $exception) {
                Log::info('Upload de vídeo já encerrado no armazenamento', ['video_id' => $video->id, 'error' => $exception->getMessage()]);
            }
        }

        DeleteStoragePaths::dispatch([$video->directory()]);

        $video->delete();
    }

    private function ensureUploading(Video $video): void
    {
        abort_unless($video->status === VideoStatus::Uploading, 409, 'Este envio já foi concluído ou cancelado.');
    }

    private function partSize(): int
    {
        return (int) config('academy.video_upload_part_bytes');
    }

    private function partCount(Video $video): int
    {
        return max(1, (int) ceil($video->original_size / $this->partSize()));
    }

    private function storedSize(Video $video): int
    {
        return Storage::size((string) $video->original_path);
    }
}
