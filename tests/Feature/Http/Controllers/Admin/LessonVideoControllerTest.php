<?php

use App\Enums\VideoStatus;
use App\Jobs\DeleteStoragePaths;
use App\Jobs\EncodeVideo;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Video;
use App\Services\Video\S3MultipartUploader;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

beforeEach(function () {
    Storage::fake();
    config(['academy.video_upload_part_bytes' => 64 * 1024 * 1024]);
});

describe('store', function () {
    test('starts a multipart upload and tells the browser how to split the file', function () {
        $lesson = Lesson::factory()->create();
        $this->mock(S3MultipartUploader::class, function (MockInterface $mock) {
            $mock->expects('start')->withArgs(fn (string $path, string $type) => str_ends_with($path, '/original.mp4') && $type === 'video/mp4')->andReturn('upload-123');
        });

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.lessons.video.store', $lesson), [
                'filename' => 'Integração.MP4',
                'size' => 300 * 1024 * 1024,
                'content_type' => 'video/mp4',
            ]);

        $video = Video::query()->sole();
        $response->assertCreated()->assertExactJson([
            'video_id' => $video->id,
            'part_size' => 64 * 1024 * 1024,
            'part_count' => 5,
        ]);
        expect($video)
            ->status->toBe(VideoStatus::Uploading)
            ->upload_id->toBe('upload-123')
            ->original_name->toBe('Integração.MP4')
            ->original_path->toBe("videos/{$video->uuid}/original.mp4");
    });

    test('discards earlier uploads that never became the lesson video', function () {
        $lesson = Lesson::factory()->create();
        $current = Video::factory()->ready()->for($lesson)->create();
        $lesson->video()->associate($current)->save();
        $abandoned = Video::factory()->for($lesson)->create(['upload_id' => 'velho']);
        $failed = Video::factory()->failed()->for($lesson)->create();
        Storage::put("{$abandoned->directory()}original.mp4", 'parte');
        $this->mock(S3MultipartUploader::class, function (MockInterface $mock) use ($abandoned) {
            $mock->expects('abort')->with($abandoned->original_path, 'velho');
            $mock->expects('start')->andReturn('novo');
        });

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.lessons.video.store', $lesson), ['filename' => 'nova.mov', 'size' => 1000])
            ->assertCreated();

        $this->assertModelMissing($abandoned);
        $this->assertModelMissing($failed);
        $this->assertModelExists($current);
        Storage::assertMissing("{$abandoned->directory()}original.mp4");
    });

    test('rejects files that are not supported videos', function (array $payload, string $field, string $message) {
        $lesson = Lesson::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.lessons.video.store', $lesson), $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('videos', 0);
    })->with([
        'larger than 2 GB' => [['filename' => 'aula.mp4', 'size' => 2 * 1024 * 1024 * 1024 + 1], 'size', 'O vídeo deve ter no máximo 2 GB.'],
        'unsupported extension' => [['filename' => 'aula.avi', 'size' => 1000], 'filename', 'Envie um vídeo MP4, M4V, MOV, MKV ou WEBM.'],
        'not a video type' => [['filename' => 'aula.mp4', 'size' => 1000, 'content_type' => 'application/pdf'], 'content_type', 'O arquivo enviado não é um vídeo.'],
    ]);
});

describe('parts', function () {
    test('signs upload urls for the requested parts', function () {
        $video = Video::factory()->create(['original_size' => 130 * 1024 * 1024, 'upload_id' => 'upload-1']);
        $this->mock(S3MultipartUploader::class, function (MockInterface $mock) {
            $mock->expects('partUrl')->twice()->andReturnUsing(fn (string $path, string $uploadId, int $part) => "https://s3.test/{$path}?part={$part}&upload={$uploadId}");
        });

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.videos.parts', $video), ['part_numbers' => [1, 3]]);

        $response->assertOk()->assertExactJson(['urls' => [
            '1' => "https://s3.test/{$video->original_path}?part=1&upload=upload-1",
            '3' => "https://s3.test/{$video->original_path}?part=3&upload=upload-1",
        ]]);
    });

    test('rejects part numbers beyond the size of the file', function () {
        $video = Video::factory()->create(['original_size' => 100 * 1024 * 1024]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.videos.parts', $video), ['part_numbers' => [3]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['part_numbers' => 'O vídeo tem apenas 2 partes.']);
    });

    test('refuses to sign parts after the upload finished', function () {
        $video = Video::factory()->queued()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.videos.parts', $video), ['part_numbers' => [1]])
            ->assertConflict();
    });
});

describe('complete', function () {
    test('assembles the parts and queues the encoding', function () {
        Queue::fake();
        $video = Video::factory()->create(['original_size' => 100 * 1024 * 1024, 'upload_id' => 'upload-1']);
        Storage::put((string) $video->original_path, 'conteúdo do vídeo');
        $this->mock(S3MultipartUploader::class, function (MockInterface $mock) use ($video) {
            $mock->expects('complete')->with($video->original_path, 'upload-1', [
                ['PartNumber' => 1, 'ETag' => '"a"'],
                ['PartNumber' => 2, 'ETag' => '"b"'],
            ]);
        });

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.videos.complete', $video), ['parts' => [
                ['part_number' => 2, 'etag' => '"b"'],
                ['part_number' => 1, 'etag' => '"a"'],
            ]]);

        $response->assertAccepted();
        expect($video->fresh())->status->toBe(VideoStatus::Queued)->upload_id->toBeNull();
        Queue::assertPushedOn('video', EncodeVideo::class, fn (EncodeVideo $job) => $job->video->is($video) && $job->connection === 'redis-video');
    });

    test('rejects an upload with missing parts', function () {
        Queue::fake();
        $video = Video::factory()->create(['original_size' => 100 * 1024 * 1024]);
        $this->mock(S3MultipartUploader::class)->shouldNotReceive('complete');

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.videos.complete', $video), ['parts' => [['part_number' => 1, 'etag' => '"a"']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parts' => 'O envio está incompleto. Envie o vídeo novamente.']);

        Queue::assertNothingPushed();
    });

    test('discards a stored file larger than the limit even if the browser declared less', function () {
        Queue::fake();
        config(['academy.video_max_upload_bytes' => 10]);
        $video = Video::factory()->create(['original_size' => 5, 'upload_id' => 'upload-1']);
        Storage::put((string) $video->original_path, str_repeat('x', 11));
        $this->mock(S3MultipartUploader::class, function (MockInterface $mock) {
            $mock->expects('complete');
            $mock->allows('abort');
        });

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.videos.complete', $video), ['parts' => [['part_number' => 1, 'etag' => '"a"']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parts' => 'O vídeo deve ter no máximo 2 GB.']);

        $this->assertModelMissing($video);
        Queue::assertPushed(DeleteStoragePaths::class, fn (DeleteStoragePaths $job) => $job->paths === [$video->directory()]);
        Queue::assertNotPushed(EncodeVideo::class);
    });
});

describe('destroy', function () {
    test('cancels a pending upload', function () {
        $video = Video::factory()->create(['upload_id' => 'upload-1']);
        $this->mock(S3MultipartUploader::class, function (MockInterface $mock) use ($video) {
            $mock->expects('abort')->with($video->original_path, 'upload-1');
        });

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.videos.destroy', $video))
            ->assertRedirect();

        $this->assertModelMissing($video);
    });

    test('keeps the current lesson video', function () {
        $video = Video::factory()->ready()->create();
        $video->lesson->video()->associate($video)->save();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.videos.destroy', $video))
            ->assertSessionHasErrors(['video' => 'Este é o vídeo atual da aula. Envie outro vídeo para substituí-lo.']);

        $this->assertModelExists($video);
    });
});
