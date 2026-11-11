<?php

namespace App\Models;

use Database\Factories\LessonRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A student's 1–5 star rating of a lesson, with optional feedback only the team reads.
 *
 * @property int $id
 * @property int $user_id
 * @property int $lesson_id
 * @property int $stars
 * @property string|null $feedback
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Lesson $lesson
 */
#[Fillable(['stars', 'feedback'])]
class LessonRating extends Model
{
    /** @use HasFactory<LessonRatingFactory> */
    use HasFactory;

    public const int MAX_FEEDBACK_LENGTH = 1000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stars' => 'integer',
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
}
