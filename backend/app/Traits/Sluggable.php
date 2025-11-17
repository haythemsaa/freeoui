<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait Sluggable
{
    protected static function bootSluggable(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->{$model->getSlugSourceColumn()})) {
                $model->slug = $model->generateUniqueSlug($model->{$model->getSlugSourceColumn()});
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty($model->getSlugSourceColumn())) {
                $model->slug = $model->generateUniqueSlug($model->{$model->getSlugSourceColumn()});
            }
        });
    }

    protected function getSlugSourceColumn(): string
    {
        return property_exists($this, 'slugSourceColumn')
            ? $this->slugSourceColumn
            : 'name';
    }

    protected function generateUniqueSlug(string $value): string
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
