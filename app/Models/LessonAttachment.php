<?php

namespace App\Models;

use App\Enums\AttachmentKind;
use Database\Factories\LessonAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $lesson_id
 * @property AttachmentKind $kind
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Lesson $lesson
 */
#[Fillable(['original_name'])]
class LessonAttachment extends Model
{
    /** @use HasFactory<LessonAttachmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AttachmentKind::class,
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
     * Get a short-lived URL that opens the file, or downloads it, with its original name.
     */
    public function temporaryUrl(bool $download = false, int $minutes = 60): string
    {
        return Storage::temporaryUrl($this->path, now()->addMinutes($minutes), [
            'ResponseContentDisposition' => sprintf(
                '%s; filename="%s"; filename*=UTF-8\'\'%s',
                $download ? 'attachment' : 'inline',
                addcslashes(Str::ascii($this->original_name), '"\\'),
                rawurlencode($this->original_name),
            ),
        ]);
    }
}
