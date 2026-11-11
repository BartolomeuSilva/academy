<?php

namespace App\Models;

use App\Models\Concerns\HasCommunityImages;
use Carbon\CarbonImmutable;
use Database\Factories\CommunityMessageFactory;
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
 * A chat message in a community channel, optionally replying to another one.
 *
 * @property int $id
 * @property int $space_id
 * @property int $user_id
 * @property int|null $reply_to_id
 * @property string $body
 * @property CarbonImmutable|null $edited_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read CommunitySpace $space
 * @property-read User $user
 * @property-read CommunityMessage|null $replyTo
 * @property-read Collection<int, CommunityReaction> $reactions
 * @property-read Collection<int, CommunityImage> $images
 */
#[Fillable(['body'])]
class CommunityMessage extends Model
{
    /** @use HasFactory<CommunityMessageFactory> */
    use HasCommunityImages, HasFactory, SoftDeletes;

    public const int MAX_BODY_LENGTH = 3000;

    /**
     * Emojis students react with.
     */
    public const array REACTIONS = ['👍', '❤️', '😂', '🎉', '🙏', '👀'];

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
     * The message this one quotes, if it was not deleted.
     *
     * @return BelongsTo<CommunityMessage, $this>
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(CommunityMessage::class, 'reply_to_id');
    }

    /**
     * @return HasMany<CommunityReaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommunityReaction::class, 'message_id');
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

    /**
     * The body as plain text, e.g. to quote it.
     */
    public function excerpt(int $limit = 160): string
    {
        return Str::limit(Str::squish(strip_tags($this->bodyHtml())), $limit);
    }
}
