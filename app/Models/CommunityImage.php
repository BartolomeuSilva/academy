<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * An image attached to a community message.
 *
 * @property int $id
 * @property string $imageable_type
 * @property int $imageable_id
 * @property string $path
 * @property int $size
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CommunityImage extends Model
{
    public const int MAX_PER_POST = 4;

    /**
     * Largest image accepted, in kilobytes.
     */
    public const int MAX_KILOBYTES = 5120;

    /**
     * @return MorphTo<Model, $this>
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A short-lived signed URL to show the image.
     */
    public function url(): string
    {
        return Storage::temporaryUrl($this->path, now()->addHours(2));
    }
}
