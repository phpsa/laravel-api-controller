<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Phpsa\LaravelApiController\Helpers;
use Illuminate\Database\Eloquent\Model;

trait HasQueryBuilder
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected Builder $builder;

    protected function getBuilder(): Builder
    {
        return $this->builder ??= $this->getNewQuery();
    }

    protected function getNewQuery(): Builder
    {
        return resolve($this->model())->newQuery();
    }

    protected function resolveRouteBinding(mixed $id): Builder
    {
        $routeKeyName = $this->getModel()->getRouteKeyName();

        return $id instanceof Model
        ? $this->getBuilder()->where($routeKeyName, $id->getAttribute($routeKeyName))
        : $this->getBuilder()->where($routeKeyName, $id);
    }

    protected function setWhereHasClause(array $where): void
    {
        [$with, $key] = explode('.', $where['key']);

        /** @scrutinizer ignore-call */
        $sub = $this->getRelatedModel($with);
        /** @scrutinizer ignore-call */
        $fields = $this->getTableColumns($sub);

        if (! in_array($key, $fields)) {
            return;
        }
        $subKey = $sub->qualifyColumn($key);

        $this->getBuilder()->whereHas(Helpers::camel($with), function ($q) use ($where, $subKey) {
            $this->setQueryBuilderWhereStatement($q, $subKey, $where);
        });
    }

    protected function setWithQuery(?array $where = null, ?array $fields = null): callable
    {
        return function ($query) use ($where, $fields) {
            if ($fields !== null && count($fields) > 0) {
                $query->select(array_unique($fields));
            }

            if ($where !== null && count($where) > 0) {
                foreach ($where as $whr) {
                    $this->setQueryBuilderWhereStatement($query, $whr['key'], $whr);
                }
            }
        };
    }

    /**
     * Queries for where clauses statement.
     * @todo For PHP 8.x , may be Type hint `$query` param to `Builder|Relation` Union types
     * @param mixed $query
     * @param string $key
     * @param mixed $where
     * @return void
     */
    protected function setQueryBuilderWhereStatement($query, string $key, $where): void
    {
        switch ($where['type']) {
            case 'In':
                if (! empty($where['values'])) {
                    $query->whereIn($key, $where['values']);
                }
                break;
            case 'NotIn':
                if (! empty($where['values'])) {
                    $query->whereNotIn($key, $where['values']);
                }
                break;
            case 'Basic':
                if ($where['value'] !== 'NULL') {
                    $query->where($key, $where['operator'], $where['value']);

                    return;
                }

                $where['operator'] === '=' ? $query->whereNull($key) : $query->whereNotNull($key);
        }
    }
}
