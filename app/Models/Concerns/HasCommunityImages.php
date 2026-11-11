<?php

namespace App\Models\Concerns;

use App\Jobs\DeleteStoragePaths;
use App\Models\CommunityImage;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Images attached to a community message, removed from storage when the message is deleted.
 */
trait HasCommunityImages
{
    /**
     * Remove the post's images once it is deleted (posts are never restored).
     */
    protected static function bootHasCommunityImages(): void
    {
        static::deleted(function (self $post) {
            $paths = $post->images()->pluck('path')->all();

            if ($paths !== []) {
                $post->images()->delete();
                DeleteStoragePaths::dispatch(array_values($paths));
            }
        });
    }

    /**
     * @return MorphMany<CommunityImage, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(CommunityImage::class, 'imageable')->orderBy('position');
    }

    /**
     * Store uploaded images on the post, in the order they were picked.
     *
     * @param  UploadedFile|array<int, UploadedFile>  $files  The `images` input of the request.
     */
    public function attachImages(UploadedFile|array $files): void
    {
        foreach (array_values(Arr::wrap($files)) as $position => $file) {
            $image = new CommunityImage;
            $image->path = $file->store('community') ?: throw new RuntimeException('Não foi possível salvar a imagem.');
            $image->size = (int) $file->getSize();
            $image->position = $position;

            $this->images()->save($image);
        }
    }

    /**
     * @return list<array{id: int, url: string}>
     */
    public function imagesForDisplay(): array
    {
        return array_values($this->images->map(fn (CommunityImage $image) => [
            'id' => $image->id,
            'url' => $image->url(),
        ])->all());
    }
}
