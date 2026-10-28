<?php

use App\Services\Video\HlsEncoding;
use App\Services\Video\VideoProbe;

test('it never upscales the source', function (int $sourceHeight, array $expected) {
    $renditions = (new HlsEncoding)->renditionsFor(new VideoProbe(60, 0, $sourceHeight, true));

    expect(array_column($renditions, 'name'))->toBe($expected);
})->with([
    '4k source' => [2160, ['480p', '720p', '1080p']],
    '1080p source' => [1080, ['480p', '720p', '1080p']],
    '720p source' => [720, ['480p', '720p']],
    '600p source' => [600, ['480p']],
    'below the ladder' => [360, ['360p']],
    'odd height below the ladder' => [361, ['360p']],
]);

test('it maps one audio stream per rendition when the source has audio', function () {
    $encoding = new HlsEncoding(threads: 2);
    $renditions = $encoding->renditionsFor(new VideoProbe(60, 1280, 720, true));

    $command = implode(' ', $encoding->hlsCommand('/tmp/in', '/tmp/out', $renditions, true));

    expect($command)
        ->toContain('[0:v]split=2[v0][v1];[v0]scale=-2:480[v0out];[v1]scale=-2:720[v1out]')
        ->toContain('-maxrate:v:0 800k')
        ->toContain('-maxrate:v:1 2000k')
        ->toContain('-var_stream_map v:0,a:0,name:480p v:1,a:1,name:720p')
        ->toContain('-threads 2')
        ->toContain('-hls_segment_filename /tmp/out/%v_%04d.ts');
});

test('it leaves audio out of the stream map for silent videos', function () {
    $encoding = new HlsEncoding;
    $renditions = $encoding->renditionsFor(new VideoProbe(60, 854, 480, false));

    $command = $encoding->hlsCommand('/tmp/in', '/tmp/out', $renditions, false);

    expect($command)->not->toContain('0:a:0')
        ->and(implode(' ', $command))->toContain('-var_stream_map v:0,name:480p');
});

test('it grabs the poster frame early in short videos', function () {
    $command = (new HlsEncoding)->posterCommand('/tmp/in', '/tmp/poster.jpg', new VideoProbe(1.5, 1920, 1080, true));

    expect(implode(' ', $command))->toContain('-ss 0.75 -i /tmp/in -frames:v 1 -vf scale=-2:720');
});
