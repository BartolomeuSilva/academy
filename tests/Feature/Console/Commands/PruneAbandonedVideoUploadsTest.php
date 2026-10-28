<?php

use App\Models\Video;
use App\Services\Video\S3MultipartUploader;
use Mockery\MockInterface;

test('it cancels uploads older than two days and keeps recent ones', function () {
    $abandoned = Video::factory()->create(['created_at' => now()->subHours(49), 'upload_id' => 'antigo']);
    $recent = Video::factory()->create(['created_at' => now()->subHours(2)]);
    $queued = Video::factory()->queued()->create(['created_at' => now()->subDays(5)]);
    $this->mock(S3MultipartUploader::class, function (MockInterface $mock) use ($abandoned) {
        $mock->expects('abort')->once()->with($abandoned->original_path, 'antigo');
    });

    $this->artisan('academy:prune-abandoned-video-uploads')
        ->expectsOutputToContain('1 envio(s) abandonado(s) removido(s).')
        ->assertSuccessful();

    $this->assertModelMissing($abandoned);
    $this->assertModelExists($recent);
    $this->assertModelExists($queued);
});
