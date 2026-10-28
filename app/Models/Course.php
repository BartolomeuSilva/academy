<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $summary
 * @property string|null $description
 * @property string|null $cover_path
 * @property PublicationStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Module> $modules
 * @property-read Collection<int, Lesson> $lessons
 */
#[Fillable(['title', 'slug', 'summary', 'description'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
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
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position');
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * Get a short-lived URL for the course cover, if one was uploaded.
     */
    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::temporaryUrl($this->cover_path, now()->addHour()) : null;
    }

    /**
     * Get every storage path owned by the course and its lessons.
     *
     * @return list<string>
     */
    public function storagePaths(): array
    {
        $attachmentPaths = LessonAttachment::query()
            ->whereIn('lesson_id', $this->lessons()->select('id'))
            ->get(['path'])
            ->map(fn (LessonAttachment $attachment) => $attachment->path)
            ->all();

        return array_values(array_filter([$this->cover_path, ...$attachmentPaths]));
    }
}
