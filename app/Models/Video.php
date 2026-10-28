<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Carbon\CarbonImmutable;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $lesson_id
 * @property string $uuid
 * @property VideoStatus $status
 * @property string $original_name
 * @property string|null $original_path
 * @property int $original_size
 * @property string|null $upload_id
 * @property string|null $playlist_path
 * @property list<array{name: string, height: int, bandwidth: int}>|null $renditions
 * @property int|null $duration_seconds
 * @property string|null $poster_path
 * @property string|null $error_message
 * @property CarbonImmutable|null $processing_started_at
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Lesson $lesson
 */
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
            'renditions' => 'array',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the storage directory that holds every file of the video.
     */
    public function directory(): string
    {
        return "videos/{$this->uuid}/";
    }

    /**
     * Get the storage directory that holds the HLS playlists and segments.
     */
    public function hlsDirectory(): string
    {
        return "videos/{$this->uuid}/hls";
    }

    /**
     * Get a short-lived URL for the poster frame, if one was generated.
     */
    public function posterUrl(): ?string
    {
        return $this->poster_path ? Storage::temporaryUrl($this->poster_path, now()->addHours(2)) : null;
    }
}
