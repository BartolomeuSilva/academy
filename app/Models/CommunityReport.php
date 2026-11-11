<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CommunityReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A student's report of a community message for the team to review.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property string|null $reason
 * @property CarbonImmutable|null $resolved_at
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read CommunityMessage|null $reportable
 */
#[Fillable(['reason'])]
class CommunityReport extends Model
{
    /** @use HasFactory<CommunityReportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
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
     * The reported message, including deleted ones.
     *
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
