<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Helpers;

trait HasQueryBuilder
{


    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;


    protected function initBuilder():void
    {
        $this->builder = $this->getNewQuery();
    }

    protected function getNewQuery(): Builder
    {
        return self::$model->newQuery();
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

        $this->builder->whereHas(Helpers::camel($with), function ($q) use ($where, $subKey) {
            $this->setQueryBuilderWhereStatement($q, $subKey, $where);
        });
    }

    protected function setWithQuery(?array $where = null, ?array $fields = null): callable
    {
        //dd($fields);
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

    protected function setQueryBuilderWhereStatement(Builder $query, string $key, $where): void
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
