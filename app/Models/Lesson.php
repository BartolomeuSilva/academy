<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $course_id
 * @property int $module_id
 * @property int|null $video_id
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
 * @property-read Video|null $video
 * @property-read Collection<int, Video> $videos
 * @property-read Collection<int, LessonProgress> $progress
 * @property-read Collection<int, Comment> $comments
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
     * The encoded video currently served to students.
     *
     * @return BelongsTo<Video, $this>
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Every video uploaded to the lesson, including pending and failed ones.
     *
     * @return HasMany<Video, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * @return HasMany<LessonProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Limit the query to lessons students can watch: published and with an encoded video.
     *
     * @param  Builder<Lesson>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('status', PublicationStatus::Published)->whereNotNull('video_id');
    }

    /**
     * Limit the query to the lessons the user may open: every lesson for admins, visible ones for students.
     *
     * @param  Builder<Lesson>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->unless($user->isAdmin(), fn (Builder $query) => $query->visible());
    }

    /**
     * Render the description's simple Markdown as HTML, escaping any raw HTML and unsafe links.
     */
    public function descriptionHtml(): ?string
    {
        return filled($this->description)
            ? Str::markdown($this->description, ['html_input' => 'escape', 'allow_unsafe_links' => false])
            : null;
    }

    /**
     * Determine whether the lesson has a playable video.
     */
    public function hasPlayableVideo(): bool
    {
        return $this->video_id !== null;
    }

    /**
     * Get every storage path owned by the lesson.
     *
     * @return list<string>
     */
    public function storagePaths(): array
    {
        return self::storagePathsFor(self::query()->whereKey($this->id));
    }

    /**
     * Get every storage path owned by the lessons in the query: attachments and video directories.
     *
     * @param  Builder<Lesson>  $lessons
     * @return list<string>
     */
    public static function storagePathsFor(Builder $lessons): array
    {
        $lessonIds = (clone $lessons)->select('id');

        $attachmentPaths = LessonAttachment::query()
            ->whereIn('lesson_id', $lessonIds)
            ->get(['path'])
            ->toBase()
            ->map(fn (LessonAttachment $attachment) => $attachment->path);

        $videoDirectories = Video::query()
            ->whereIn('lesson_id', (clone $lessons)->select('id'))
            ->get(['uuid'])
            ->toBase()
            ->map(fn (Video $video) => $video->directory());

        return array_values($attachmentPaths->merge($videoDirectories)->all());
    }
}
