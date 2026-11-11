<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property string $emoji
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CommunityMessage $message
 * @property-read User $user
 */
class CommunityReaction extends Model
{
    /**
     * @return BelongsTo<CommunityMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(CommunityMessage::class, 'message_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
