<?php

namespace Phpsa\LaravelApiController\Model\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait WithSoftDeletes
{
    /**
     * Adding this scope will allow you to include deleted items in your search.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query query builder passed by laravel
     * @param string|null $enabled value passed in the url / scope caller
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTrashed(Builder $query, ?string $enabled)
    {
        $isTrue = $enabled === null ? true : filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        return $isTrue ? $query->withTrashed() : $query;
    }

    /**
     * adding this scope will return only deleted items for your search.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query query builder passed by laravel
     * @param string|null $enabled value passed in the url / scope caller
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnlyTrashed(Builder $query, ?string $enabled)
    {
        $isTrue = $enabled === null ? true : filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        return $isTrue ? $query->onlyTrashed() : $query;
    }
}
