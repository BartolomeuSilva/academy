<?php

use App\Enums\VideoStatus;
use App\Jobs\EncodeVideo;
use App\Models\Lesson;
use App\Models\Video;
use App\Services\Video\HlsEncoding;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Generate a short synthetic video with FFmpeg and store it as the original of a queued video.
 */
function queuedVideoWithOriginal(Lesson $lesson, string $ffmpegInput, string $size): Video
{
    $video = Video::factory()->queued()->for($lesson)->create();
    $path = tempnam(sys_get_temp_dir(), 'academy-video-').'.mp4';

    Process::run(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', ...explode(' ', $ffmpegInput), '-t', '4', '-s', $size, '-pix_fmt', 'yuv420p', $path])->throw();
    Storage::put((string) $video->original_path, (string) file_get_contents($path));
    unlink($path);

    return $video;
}

beforeEach(function () {
    if (Process::run(['ffmpeg', '-version'])->failed()) {
        $this->markTestSkipped('FFmpeg não está instalado.');
    }

    Storage::fake();
    config(['academy.video_work_directory' => sys_get_temp_dir().'/academy-video-tests']);
});

test('it encodes the original into hls, publishes it and deletes the original and the replaced video', function () {
    $lesson = Lesson::factory()->create();
    $replaced = Video::factory()->ready()->for($lesson)->create();
    $lesson->video()->associate($replaced)->save();
    Storage::put("{$replaced->hlsDirectory()}/master.m3u8", '#EXTM3U');
    $video = queuedVideoWithOriginal($lesson, '-f lavfi -i testsrc=rate=24 -f lavfi -i sine=frequency=440', '1280x720');
    $originalPath = (string) $video->original_path;

    (new EncodeVideo($video))->handle(app(HlsEncoding::class));

    $video->refresh();
    expect($video)
        ->status->toBe(VideoStatus::Ready)
        ->duration_seconds->toBe(4)
        ->original_path->toBeNull()
        ->and(array_column($video->renditions, 'name'))->toBe(['480p', '720p'])
        ->and($lesson->fresh()->video_id)->toBe($video->id);
    expect(Storage::get("{$video->hlsDirectory()}/master.m3u8"))->toContain('480p.m3u8')->toContain('720p.m3u8');
    expect(Storage::get("{$video->hlsDirectory()}/720p.m3u8"))->toContain('720p_0000.ts')->toContain('#EXT-X-ENDLIST');
    Storage::assertExists(["{$video->hlsDirectory()}/720p_0000.ts", "videos/{$video->uuid}/poster.jpg"]);
    Storage::assertMissing([$originalPath, "{$replaced->hlsDirectory()}/master.m3u8"]);
    $this->assertModelMissing($replaced);
})->group('integration');

test('it encodes videos without an audio track', function () {
    $video = queuedVideoWithOriginal(Lesson::factory()->create(), '-f lavfi -i testsrc=rate=24', '640x360');

    (new EncodeVideo($video))->handle(app(HlsEncoding::class));

    expect($video->fresh())
        ->status->toBe(VideoStatus::Ready)
        ->renditions->toBe([['name' => '360p', 'height' => 360, 'bandwidth' => 928_000]]);
})->group('integration');

test('it marks the video as failed and removes its files when the upload is not a video', function () {
    $video = Video::factory()->queued()->create();
    Storage::put((string) $video->original_path, 'isto não é um vídeo');
    $job = new EncodeVideo($video);

    try {
        $job->handle(app(HlsEncoding::class));
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    expect($video->fresh())
        ->status->toBe(VideoStatus::Failed)
        ->error_message->toBe('O arquivo enviado não é um vídeo válido.');
    Storage::assertMissing((string) $video->original_path);
    expect($video->lesson->fresh()->video_id)->toBeNull();
})->group('integration');

test('it skips videos that are no longer waiting for encoding', function () {
    $video = Video::factory()->ready()->create();

    (new EncodeVideo($video))->handle(app(HlsEncoding::class));

    expect($video->fresh()->status)->toBe(VideoStatus::Ready);
});
