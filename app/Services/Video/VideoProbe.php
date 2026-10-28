<?php

namespace App\Services\Video;

use RuntimeException;

/**
 * The facts about a source video that the encoder needs, read from ffprobe's JSON output.
 */
final readonly class VideoProbe
{
    public function __construct(
        public float $durationSeconds,
        public int $width,
        public int $height,
        public bool $hasAudio,
    ) {}

    /**
     * Build the probe from the output of `ffprobe -print_format json -show_format -show_streams`.
     */
    public static function fromFfprobeJson(string $json): self
    {
        /** @var array{streams?: list<array<string, mixed>>, format?: array<string, mixed>}|null $data */
        $data = json_decode($json, true);

        $streams = $data['streams'] ?? [];
        $video = collect($streams)->first(fn (array $stream) => ($stream['codec_type'] ?? null) === 'video'
            && ! (($stream['disposition']['attached_pic'] ?? 0) === 1));

        if (! is_array($video)) {
            throw new RuntimeException('O arquivo enviado não contém uma faixa de vídeo.');
        }

        $duration = (float) ($data['format']['duration'] ?? $video['duration'] ?? 0);

        if ($duration <= 0) {
            throw new RuntimeException('Não foi possível ler a duração do vídeo.');
        }

        [$width, $height] = [(int) ($video['width'] ?? 0), (int) ($video['height'] ?? 0)];

        if (in_array(abs((int) ($video['tags']['rotate'] ?? $video['side_data_list'][0]['rotation'] ?? 0)), [90, 270], true)) {
            [$width, $height] = [$height, $width];
        }

        return new self(
            durationSeconds: $duration,
            width: $width,
            height: $height,
            hasAudio: collect($streams)->contains(fn (array $stream) => ($stream['codec_type'] ?? null) === 'audio'),
        );
    }
}
