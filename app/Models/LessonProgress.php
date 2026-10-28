<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LessonProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $lesson_id
 * @property int $last_position_seconds
 * @property CarbonImmutable|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Lesson $lesson
 */
class LessonProgress extends Model
{
    /** @use HasFactory<LessonProgressFactory> */
    use HasFactory;

    /**
     * Share of the video that marks a lesson as completed.
     */
    public const float COMPLETION_RATIO = 0.9;

    protected $table = 'lesson_progress';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'last_position_seconds' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_position_seconds' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Store the playback position, completing the lesson when playback first reaches the completion threshold.
     *
     * Crossing the threshold only once keeps a lesson the student unmarked from completing again by itself.
     */
    public function recordPosition(int $seconds, ?int $durationSeconds): void
    {
        $seconds = $durationSeconds !== null ? min($seconds, $durationSeconds) : $seconds;

        if ($durationSeconds !== null && $durationSeconds > 0) {
            $threshold = (int) ceil($durationSeconds * self::COMPLETION_RATIO);

            if ($seconds >= $threshold && $this->last_position_seconds < $threshold) {
                $this->completed_at ??= now();
            }
        }

        $this->last_position_seconds = $seconds;
    }

    /**
     * Determine whether the lesson was completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
