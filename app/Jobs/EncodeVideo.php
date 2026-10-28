<?php

namespace App\Jobs;

use App\Enums\VideoStatus;
use App\Models\Video;
use App\Services\Video\HlsEncoding;
use App\Services\Video\VideoProbe;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Encodes an uploaded original into an HLS ladder, publishes it on the lesson and deletes the original.
 */
class EncodeVideo implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const int MAX_DURATION_SECONDS = 3600;

    public int $tries = 1;

    public int $timeout = 7200;

    public bool $failOnTimeout = true;

    public function __construct(public Video $video)
    {
        $this->onConnection('redis-video')->onQueue('video');
    }

    public function uniqueId(): string
    {
        return (string) $this->video->id;
    }

    /**
     * Execute the job.
     */
    public function handle(HlsEncoding $encoding): void
    {
        $video = $this->video;

        if ($video->status !== VideoStatus::Queued || $video->original_path === null) {
            return;
        }

        $video->forceFill(['status' => VideoStatus::Processing, 'processing_started_at' => now(), 'error_message' => null])->save();

        $workDirectory = rtrim((string) config('academy.video_work_directory'), '/')."/{$video->uuid}";
        $outputDirectory = "{$workDirectory}/hls";
        $input = "{$workDirectory}/original";

        try {
            File::ensureDirectoryExists($outputDirectory);
            $this->download($video->original_path, $input);

            $probe = $this->probe($input);

            if ($probe->durationSeconds > self::MAX_DURATION_SECONDS + 59) {
                throw new RuntimeException('O vídeo tem mais de 1 hora. Envie um arquivo mais curto.');
            }

            $renditions = $encoding->renditionsFor($probe);
            $this->run($encoding->hlsCommand($input, $outputDirectory, $renditions, $probe->hasAudio), 'converter o vídeo');
            $this->run($encoding->posterCommand($input, "{$workDirectory}/poster.jpg", $probe), 'gerar a imagem de capa do vídeo');

            $this->uploadDirectory($outputDirectory, $video->hlsDirectory());
            $this->upload("{$workDirectory}/poster.jpg", "videos/{$video->uuid}/poster.jpg");

            $this->publish($video, $probe, $renditions);
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    /**
     * Mark the video as failed and drop its files; the admin uploads it again.
     */
    public function failed(?Throwable $exception): void
    {
        $video = $this->video->fresh();

        if (! $video) {
            return;
        }

        Log::warning('Falha ao converter vídeo', ['video_id' => $video->id, 'exception' => $exception?->getMessage()]);

        $video->forceFill([
            'status' => VideoStatus::Failed,
            'error_message' => $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Não foi possível converter o vídeo. Verifique o arquivo e envie novamente.',
            'original_path' => null,
        ])->save();

        DeleteStoragePaths::dispatch([$video->directory()]);
    }

    /**
     * Swap the lesson to the new video, then delete the original and the video it replaced.
     *
     * @param  list<array{name: string, height: int, bandwidth: int, video_kbps: int, audio_kbps: int}>  $renditions
     */
    private function publish(Video $video, VideoProbe $probe, array $renditions): void
    {
        DB::transaction(function () use ($video, $probe, $renditions) {
            $lesson = $video->lesson()->lockForUpdate()->firstOrFail();
            $replaced = $lesson->video;
            $originalPath = $video->original_path;

            $video->forceFill([
                'status' => VideoStatus::Ready,
                'playlist_path' => "{$video->hlsDirectory()}/master.m3u8",
                'poster_path' => "videos/{$video->uuid}/poster.jpg",
                'duration_seconds' => (int) round($probe->durationSeconds),
                'renditions' => array_map(fn (array $rendition) => [
                    'name' => $rendition['name'],
                    'height' => $rendition['height'],
                    'bandwidth' => $rendition['bandwidth'],
                ], $renditions),
                'original_path' => null,
                'processed_at' => now(),
            ])->save();

            $lesson->video()->associate($video)->save();

            $paths = array_filter([$originalPath, $replaced?->directory()]);
            $replaced?->delete();

            DeleteStoragePaths::dispatch(array_values($paths));
        });
    }

    private function probe(string $input): VideoProbe
    {
        $result = Process::timeout(120)->run(['ffprobe', '-v', 'error', '-print_format', 'json', '-show_format', '-show_streams', $input]);

        if ($result->failed()) {
            throw new RuntimeException('O arquivo enviado não é um vídeo válido.');
        }

        return VideoProbe::fromFfprobeJson($result->output());
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command, string $step): void
    {
        $result = Process::timeout($this->timeout - 300)->run($command);

        if ($result->failed()) {
            Log::warning("FFmpeg falhou ao {$step}", ['video_id' => $this->video->id, 'error' => mb_substr($result->errorOutput(), -2000)]);

            throw new RuntimeException("Não foi possível {$step}. Verifique se o arquivo é um vídeo válido.");
        }
    }

    private function download(string $path, string $destination): void
    {
        $source = Storage::readStream($path) ?: throw new RuntimeException('O arquivo original do vídeo não foi encontrado.');
        $target = fopen($destination, 'wb') ?: throw new RuntimeException('Não foi possível gravar o arquivo temporário.');

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }
    }

    private function uploadDirectory(string $localDirectory, string $remoteDirectory): void
    {
        foreach (File::files($localDirectory) as $file) {
            $this->upload($file->getPathname(), "{$remoteDirectory}/{$file->getFilename()}");
        }
    }

    private function upload(string $localPath, string $remotePath): void
    {
        $stream = fopen($localPath, 'rb') ?: throw new RuntimeException("Não foi possível ler {$localPath}.");

        try {
            Storage::writeStream($remotePath, $stream, ['ContentType' => $this->contentType($localPath)])
                ?: throw new RuntimeException("Não foi possível enviar {$remotePath} para o armazenamento.");
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function contentType(string $path): string
    {
        return match (pathinfo($path, PATHINFO_EXTENSION)) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            'jpg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }
}
