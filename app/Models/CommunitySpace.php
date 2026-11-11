<?php

namespace App\Models;

use Database\Factories\CommunitySpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A community area, e.g. one per product, "Geral" or the team-only "Avisos".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $staff_only Only admins start topics here; everyone may reply.
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CommunityTopic> $topics
 */
#[Fillable(['name', 'slug', 'description', 'staff_only'])]
class CommunitySpace extends Model
{
    /** @use HasFactory<CommunitySpaceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'staff_only' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<CommunityTopic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(CommunityTopic::class, 'space_id');
    }
}
