<?php

namespace Phpsa\LaravelApiController\Contracts;

use Illuminate\Support\Collection;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\UriParser;

trait Parser
{
    /**
     * UriParser instance.
     *
     * @var \Phpsa\LaravelApiController\UriParser
     */
    protected static $uriParser;

    protected $originalQueryParams;

    protected function getUriParser($request)
    {
        if (is_null(self::$uriParser)) {
            self::$uriParser = new UriParser($request, config('laravel-api-controller.parameters.filter'));
        }

        return self::$uriParser;
    }

    /**
     * Method to add extra request parameters to the request instance.
     *
     * @param mixed $request
     * @param array $extraParams
     */
    protected function addCustomParams($request, array $extraParams = []): void
    {
        $this->originalQueryParams = $request->query();

        $all = $request->all();
        $new = Helpers::array_merge_request($all, $extraParams);
        $request->replace($new);
    }

    /**
     * Parses our include joins.
     */
    protected function parseIncludeParams(): void
    {
        $field = config('laravel-api-controller.parameters.include');

        if (empty($field)) {
            return;
        }

        $includes = $this->request->input($field);

        if (empty($includes)) {
            return;
        }

        $withs = array_flip(
            $this->/** @scrutinizer ignore-call */filterAllowedIncludes(explode(',', $includes))
        );

        foreach ($withs as $with => $idx) {
            /** @scrutinizer ignore-call */
            $sub = $this->getRelatedModel($with);
            $fields = $this->getIncludesFields($with);

            $where = array_filter(self::$uriParser->whereParameters(), function ($where) use ($with) {
                return strpos($where['key'], Helpers::snake($with).'.') !== false;
            });

            if (! empty($fields)) {
                $fields[] = $sub->getKeyName();
            }

            if (! empty($where)) {
                $where = array_map(function ($whr) use ($with, $sub) {
                    $key = str_replace(Helpers::snake($with).'.', '', $whr['key']);
                    $whr['key'] = $sub->qualifyColumn($key);

                    return $whr;
                }, $where);
            }

            $withs[$with] = $this->setWithQuery($where, $fields);
        }

        $this->repository->with($withs);
    }

    /**
     * Parses our sort parameters.
     */
    protected function parseSortParams(): void
    {
        $sorts = $this->getSortValue();
        $withSorts = collect([]);

        foreach ($sorts as $sort) {
            $sortP = explode(' ', $sort);
            $sortF = $sortP[0];
            $sortD = ! empty($sortP[1]) && strtolower($sortP[1]) === 'desc' ? 'desc' : 'asc';

            if (strpos($sortF, '.') > 0) {
                $withSorts[$sortF] = $sortD;
                continue;
            }
            /** @scrutinizer ignore-call */
            $tableColumns = $this->getTableColumns();

            if (empty($sortF) || ! in_array($sortF, $tableColumns)) {
                continue;
            }

            $this->repository->orderBy($sortF, $sortD);
        }

        if ($withSorts->count() > 0) {
            $this->parseJoinSorts($withSorts);
        }
    }

    protected function parseJoinSorts(Collection $sorts)
    {
        $currentTable = self::$model->getTable();

        $fields = array_map(function ($field) use ($currentTable) {
            return $currentTable.'.'.$field;
        }, $this->parseFieldParams());

        $this->repository->select($fields);

        foreach ($sorts as $sortF => $sortD) {
            [$with, $key] = explode('.', $sortF);
            $relation = self::$model->{Helpers::camel($with)}();
            $type = class_basename(get_class($relation));

            if ($type === 'HasOne') {
                $foreignKey = $relation->getForeignKeyName();
                $localKey = $relation->getLocalKeyName();
            } elseif ($type === 'BelongsTo') {
                $foreignKey = $relation->getOwnerKeyName();
                $localKey = $relation->getForeignKeyName();
            } else {
                continue;
            }

            $withConnection = $relation->getRelated()->getConnection()->getDatabaseName();

            $withTable = $relation->getRelated()->getTable();

            $withTableName = strpos($withTable, '.') === false ? $withConnection.'.'.$withTable : $withTable;

            $this->repository->leftJoin($withTableName, "{$withTableName}.{$foreignKey}", "{$currentTable}.{$localKey}");
            $this->repository->orderBy("{$withTableName}.{$key}", $sortD);
        }
    }

    /**
     * gets the sort value.
     *
     * @returns array
     */
    protected function getSortValue(): array
    {
        $sortField = config('laravel-api-controller.parameters.sort');
        $sort = $this->request->has($sortField) ? $this->request->input($sortField) : $this->defaultSort;

        if (! $sort) {
            return [];
        }

        return is_array($sort) ? $sort : explode(',', $sort);
    }

    /**
     * parses our filter parameters.
     */
    protected function parseFilterParams(): void
    {
        $where = self::$uriParser->whereParameters();

        if (empty($where)) {
            return;
        }

        /** @scrutinizer ignore-call */
        $tableColumns = $this->getTableColumns();
        $table = self::$model->getTable();

        foreach ($where as $whr) {
            if (strpos($whr['key'], '.') > 0) {
                $this->setWhereHasClause($whr);
                continue;
            } elseif (! in_array($whr['key'], $tableColumns)) {
                continue;
            }
            $this->setQueryBuilderWhereStatement($this->repository, $table.'.'.$whr['key'], $whr);
        }
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

        $this->repository->whereHas(Helpers::camel($with), function ($q) use ($where, $subKey) {
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

    protected function setQueryBuilderWhereStatement($query, $key, $where): void
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

    /**
     * parses the fields to return.
     *
     * @return array
     */
    protected function parseFieldParams(): array
    {
        /** @scrutinizer ignore-call */
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->/** @scrutinizer ignore-call */ getDefaultFields());

        /** @scrutinizer ignore-call */
        $tableColumns = $this->getTableColumns();
        foreach ($fields as $key => $field) {
            if ($field === '*' || in_array($field, $tableColumns)) {
                continue;
            }
            unset($fields[$key]);
        }

        return $fields;
    }

    /**
     * Parses an includes fields and returns as an array.
     *
     * @param string $include - the table definer
     *
     * @return array
     */
    protected function getIncludesFields(string $include): array
    {
        /** @scrutinizer ignore-call */
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->/** @scrutinizer ignore-call */ getDefaultFields());

        $relation = self::$model->{$include}();
        $type = $relation->getRelated();
        /** @scrutinizer ignore-call */
        $tableColumns = $this->getTableColumns($type);

        foreach ($fields as $key => $field) {
            $parts = explode('.', $field);
            if (strpos($field, Helpers::snake($include).'.') === false || ! in_array(end($parts), $tableColumns)) {
                unset($fields[$key]);

                continue;
            }

            $fields[$key] = str_replace(Helpers::snake($include).'.', '', $field);
        }

        return $fields;
    }

    /**
     * parses the limit value.
     *
     * @return int
     */
    protected function parseLimitParams(): int
    {
        $limitField = config('laravel-api-controller.parameters.limit') ?? 'limit';
        $limit = $this->request->has($limitField) ? intval($this->request->input($limitField)) : $this->defaultLimit;

        if ($this->maximumLimit && ($limit > $this->maximumLimit || ! $limit)) {
            $limit = $this->maximumLimit;
        }

        return $limit;
    }
}
