<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommunityTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $space_id
 * @property int $user_id
 * @property string $title
 * @property string $body
 * @property int|null $solution_reply_id
 * @property CarbonImmutable|null $pinned_at
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable $last_activity_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read CommunitySpace $space
 * @property-read User $user
 * @property-read CommunityReply|null $solution
 * @property-read Collection<int, CommunityReply> $replies
 * @property-read int|null $replies_count
 */
#[Fillable(['title', 'body'])]
class CommunityTopic extends Model
{
    /** @use HasFactory<CommunityTopicFactory> */
    use HasFactory, SoftDeletes;

    public const int MAX_TITLE_LENGTH = 160;

    public const int MAX_BODY_LENGTH = 5000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pinned_at' => 'datetime',
            'edited_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommunitySpace, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(CommunitySpace::class, 'space_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CommunityReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(CommunityReply::class, 'topic_id');
    }

    /**
     * The reply the author or the team marked as the answer.
     *
     * @return BelongsTo<CommunityReply, $this>
     */
    public function solution(): BelongsTo
    {
        return $this->belongsTo(CommunityReply::class, 'solution_reply_id');
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
