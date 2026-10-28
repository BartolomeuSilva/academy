<?php

namespace Database\Factories;

use App\Enums\VideoStatus;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'lesson_id' => Lesson::factory(),
            'uuid' => $uuid,
            'status' => VideoStatus::Uploading,
            'original_name' => 'aula.mp4',
            'original_path' => "videos/{$uuid}/original.mp4",
            'original_size' => 300 * 1024 * 1024,
            'upload_id' => Str::random(32),
        ];
    }

    /**
     * Indicate that the upload finished and the video waits for encoding.
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::Queued,
            'upload_id' => null,
        ]);
    }

    /**
     * Indicate that the video was encoded and can be played.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::Ready,
            'upload_id' => null,
            'original_path' => null,
            'playlist_path' => "videos/{$attributes['uuid']}/hls/master.m3u8",
            'poster_path' => "videos/{$attributes['uuid']}/poster.jpg",
            'duration_seconds' => 600,
            'renditions' => [
                ['name' => '480p', 'height' => 480, 'bandwidth' => 928_000],
                ['name' => '720p', 'height' => 720, 'bandwidth' => 2_128_000],
            ],
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that encoding failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::Failed,
            'upload_id' => null,
            'original_path' => null,
            'error_message' => 'O arquivo enviado não contém uma faixa de vídeo.',
        ]);
    }
}
