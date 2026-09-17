<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class UniqueSlugGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generate(
        string $source,
        string $modelClass,
        string $column = 'slug',
        string $fallback = 'item',
    ): string {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException(
                sprintf('%s must be an Eloquent model.', $modelClass)
            );
        }

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column) !== 1) {
            throw new InvalidArgumentException(
                sprintf('%s is not a valid column name.', $column)
            );
        }

        $baseSlug = Str::slug($source);

        if ($baseSlug === '') {
            $baseSlug = Str::slug($fallback);
        }

        if ($baseSlug === '') {
            throw new InvalidArgumentException(
                'The fallback must contain characters that can form a slug.'
            );
        }

        /** @var Model $model */
        $model = new $modelClass;
        $query = $model->newQueryWithoutScopes();
        $slug = $baseSlug;
        $suffix = 2;

        while ((clone $query)->where($column, $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
