<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GenerateUniqueSlug
{
    /**
     * Build a slug from the value that is unique within the given query.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function handle(Builder $query, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ((clone $query)->where('slug', $slug)->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
