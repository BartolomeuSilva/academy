<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Course $course
 * @property-read Collection<int, Lesson> $lessons
 */
#[Fillable(['title'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /**
     * Get every storage path owned by the module's lessons.
     *
     * @return list<string>
     */
    public function storagePaths(): array
    {
        return array_values(LessonAttachment::query()
            ->whereIn('lesson_id', $this->lessons()->select('id'))
            ->get(['path'])
            ->map(fn (LessonAttachment $attachment) => $attachment->path)
            ->all());
    }
}
