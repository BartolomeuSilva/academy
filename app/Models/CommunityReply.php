<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommunityReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $topic_id
 * @property int $user_id
 * @property string $body
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read CommunityTopic $topic
 * @property-read User $user
 */
#[Fillable(['body'])]
class CommunityReply extends Model
{
    /** @use HasFactory<CommunityReplyFactory> */
    use HasFactory, SoftDeletes;

    public const int MAX_BODY_LENGTH = 3000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommunityTopic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(CommunityTopic::class, 'topic_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<CommunityReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(CommunityReport::class, 'reportable');
    }

    /**
     * Render the body's simple Markdown as HTML, escaping any raw HTML and unsafe links.
     */
    public function bodyHtml(): string
    {
        return Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}
