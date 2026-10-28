<?php

namespace App\Console\Commands;

use App\Enums\VideoStatus;
use App\Jobs\DeleteStoragePaths;
use App\Models\Video;
use App\Services\Video\S3MultipartUploader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('academy:prune-abandoned-video-uploads {--hours=48 : Idade mínima do envio, em horas}')]
#[Description('Cancela envios de vídeo que nunca foram concluídos e apaga as partes já enviadas')]
class PruneAbandonedVideoUploads extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(S3MultipartUploader $uploader): int
    {
        $pruned = 0;

        Video::query()
            ->where('status', VideoStatus::Uploading)
            ->where('created_at', '<', now()->subHours((int) $this->option('hours')))
            ->each(function (Video $video) use ($uploader, &$pruned) {
                try {
                    $uploader->abort((string) $video->original_path, (string) $video->upload_id);
                } catch (Throwable) {
                    // The upload may already be gone from storage; the record is removed either way.
                }

                DeleteStoragePaths::dispatch([$video->directory()]);
                $video->delete();
                $pruned++;
            });

        $this->components->info("{$pruned} envio(s) abandonado(s) removido(s).");

        return self::SUCCESS;
    }
}
