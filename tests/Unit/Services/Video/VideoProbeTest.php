<?php

use App\Services\Video\VideoProbe;

function ffprobeJson(array $streams, array $format = ['duration' => '125.4']): string
{
    return json_encode(['streams' => $streams, 'format' => $format], JSON_THROW_ON_ERROR);
}

test('it reads duration, size and audio from ffprobe output', function () {
    $probe = VideoProbe::fromFfprobeJson(ffprobeJson([
        ['codec_type' => 'video', 'width' => 1920, 'height' => 1080],
        ['codec_type' => 'audio'],
    ]));

    expect($probe)
        ->durationSeconds->toBe(125.4)
        ->width->toBe(1920)
        ->height->toBe(1080)
        ->hasAudio->toBeTrue();
});

test('it swaps the dimensions of rotated phone videos', function () {
    $probe = VideoProbe::fromFfprobeJson(ffprobeJson([
        ['codec_type' => 'video', 'width' => 1920, 'height' => 1080, 'side_data_list' => [['rotation' => -90]]],
    ]));

    expect([$probe->width, $probe->height, $probe->hasAudio])->toBe([1080, 1920, false]);
});

test('it ignores cover art embedded as a video stream', function () {
    $json = ffprobeJson([
        ['codec_type' => 'video', 'width' => 600, 'height' => 600, 'disposition' => ['attached_pic' => 1]],
        ['codec_type' => 'audio'],
    ]);

    expect(fn () => VideoProbe::fromFfprobeJson($json))
        ->toThrow(RuntimeException::class, 'O arquivo enviado não contém uma faixa de vídeo.');
});

test('it rejects files without a readable duration', function () {
    $json = ffprobeJson([['codec_type' => 'video', 'width' => 640, 'height' => 360]], []);

    expect(fn () => VideoProbe::fromFfprobeJson($json))
        ->toThrow(RuntimeException::class, 'Não foi possível ler a duração do vídeo.');
});
