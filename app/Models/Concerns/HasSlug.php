<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (self $model): void {
            if (filled($model->slug)) {
                return;
            }

            $model->slug = $model->uniqueSlug($model->slugSource());
        });
    }

    protected function slugSource(): string
    {
        return (string) $this->getAttribute('name');
    }

    protected function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            return $base;
        }

        $slug = $base;
        $suffix = 2;

        while (
            static::withoutGlobalScopes()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}