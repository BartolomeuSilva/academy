<?php

namespace App\Services\Video;

/**
 * Builds the FFmpeg commands that turn a source video into an adaptive HLS ladder.
 */
final class HlsEncoding
{
    public const int SEGMENT_SECONDS = 6;

    /**
     * Height => [video bitrate cap in kbps, audio bitrate in kbps].
     *
     * @var array<int, array{int, int}>
     */
    private const array LADDER = [
        480 => [800, 128],
        720 => [2000, 128],
        1080 => [3500, 128],
    ];

    /**
     * @param  int  $threads  FFmpeg threads, kept low so encoding does not starve other services on the host.
     */
    public function __construct(private int $threads = 2) {}

    /**
     * Pick the renditions for a source, never upscaling. Sources smaller than the
     * lowest rung get a single rendition at their own height.
     *
     * @return list<array{name: string, height: int, bandwidth: int, video_kbps: int, audio_kbps: int}>
     */
    public function renditionsFor(VideoProbe $probe): array
    {
        $heights = array_values(array_filter(array_keys(self::LADDER), fn (int $height) => $height <= $probe->height));

        if ($heights === []) {
            $sourceHeight = max(2, $probe->height - ($probe->height % 2));

            return [$this->rendition($sourceHeight, ...self::LADDER[480])];
        }

        return array_map(fn (int $height) => $this->rendition($height, ...self::LADDER[$height]), $heights);
    }

    /**
     * The FFmpeg command that writes `master.m3u8`, one playlist per rendition and the segments into the output directory.
     *
     * @param  list<array{name: string, height: int, bandwidth: int, video_kbps: int, audio_kbps: int}>  $renditions
     * @return list<string>
     */
    public function hlsCommand(string $input, string $outputDirectory, array $renditions, bool $hasAudio): array
    {
        $count = count($renditions);
        $splitOutputs = implode('', array_map(fn (int $index) => "[v{$index}]", array_keys($renditions)));
        $filters = ["[0:v]split={$count}{$splitOutputs}"];

        foreach ($renditions as $index => $rendition) {
            $filters[] = "[v{$index}]scale=-2:{$rendition['height']}[v{$index}out]";
        }

        $command = ['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', '-i', $input, '-filter_complex', implode(';', $filters)];

        foreach ($renditions as $index => $rendition) {
            $command = [
                ...$command,
                '-map', "[v{$index}out]",
                "-c:v:{$index}", 'libx264',
                "-crf:v:{$index}", '23',
                "-maxrate:v:{$index}", "{$rendition['video_kbps']}k",
                "-bufsize:v:{$index}", ($rendition['video_kbps'] * 2).'k',
            ];
        }

        if ($hasAudio) {
            foreach ($renditions as $index => $rendition) {
                $command = [...$command, '-map', '0:a:0', "-c:a:{$index}", 'aac', "-b:a:{$index}", "{$rendition['audio_kbps']}k", "-ac:a:{$index}", '2'];
            }
        }

        $streamMap = implode(' ', array_map(
            fn (int $index, array $rendition) => $hasAudio
                ? "v:{$index},a:{$index},name:{$rendition['name']}"
                : "v:{$index},name:{$rendition['name']}",
            array_keys($renditions),
            $renditions,
        ));

        return [
            ...$command,
            '-preset', 'veryfast',
            '-profile:v', 'main',
            '-pix_fmt', 'yuv420p',
            '-sc_threshold', '0',
            '-force_key_frames', 'expr:gte(t,n_forced*'.self::SEGMENT_SECONDS.')',
            '-threads', (string) $this->threads,
            '-f', 'hls',
            '-hls_time', (string) self::SEGMENT_SECONDS,
            '-hls_playlist_type', 'vod',
            '-hls_flags', 'independent_segments',
            '-hls_segment_filename', "{$outputDirectory}/%v_%04d.ts",
            '-master_pl_name', 'master.m3u8',
            '-var_stream_map', $streamMap,
            "{$outputDirectory}/%v.m3u8",
        ];
    }

    /**
     * The FFmpeg command that extracts a 720p (or smaller) JPEG poster frame.
     *
     * @return list<string>
     */
    public function posterCommand(string $input, string $output, VideoProbe $probe): array
    {
        $seconds = number_format(min(2.0, $probe->durationSeconds / 2), 2, '.', '');
        $height = min(720, $probe->height - ($probe->height % 2));

        return ['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', '-ss', $seconds, '-i', $input, '-frames:v', '1', '-vf', "scale=-2:{$height}", '-q:v', '3', $output];
    }

    /**
     * @return array{name: string, height: int, bandwidth: int, video_kbps: int, audio_kbps: int}
     */
    private function rendition(int $height, int $videoKbps, int $audioKbps): array
    {
        return [
            'name' => "{$height}p",
            'height' => $height,
            'bandwidth' => ($videoKbps + $audioKbps) * 1000,
            'video_kbps' => $videoKbps,
            'audio_kbps' => $audioKbps,
        ];
    }
}
