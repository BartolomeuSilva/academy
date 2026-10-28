<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Database\Factories\LessonFactory;
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
 * @property int $module_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property PublicationStatus $status
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Course $course
 * @property-read Module $module
 * @property-read Collection<int, LessonAttachment> $attachments
 */
#[Fillable(['title', 'slug', 'description'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return HasMany<LessonAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class)->orderBy('position');
    }

    /**
     * Get every storage path owned by the lesson.
     *
     * @return list<string>
     */
    public function storagePaths(): array
    {
        return array_values($this->attachments()
            ->get(['path'])
            ->map(fn (LessonAttachment $attachment) => $attachment->path)
            ->all());
    }
}
