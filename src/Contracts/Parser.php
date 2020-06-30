<?php

namespace Phpsa\LaravelApiController\Contracts;

use Illuminate\Support\Collection;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\UriParser;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Str;

trait Parser
{
    /**
     * UriParser instance.
     *
     * @var \Phpsa\LaravelApiController\UriParser
     */
    protected static $uriParser;

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

        $withs = explode(',', $includes);

        /** @scrutinizer ignore-call */
        $withs = array_flip($this->filterAllowedIncludes($withs));

        foreach ($withs as $with => $idx) {
            $sub = self::$model->{$with}()->getRelated();
            $fields = $this->getIncludesFields($with);
            $where = array_filter(self::$uriParser->whereParameters(), function ($where) use ($with) {
                return strpos($where['key'], $with.'.') !== false;
            });

            if (! empty($fields)) {
                $fields[] = $sub->getKeyName();
            }

            if (! empty($where)) {
                $where = array_map(function ($whr) use ($with, $sub) {
                    $key = str_replace($with.'.', '', $whr['key']);
                    $whr['key'] =  $sub->qualifyColumn($key);

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
                //$this->parseJoinSort($sortF, $sortD);
                $withSorts[$sortF]=$sortD;
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
        $currentTable= self::$model->getTable();

        $fields = array_map(function ($field) use ($currentTable) {
            return $currentTable . "." . $field;
        }, $this->parseFieldParams());

        $this->repository->select($fields);

        foreach ($sorts as $sortF => $sortD) {
            [$with, $key] = explode('.', $sortF);
            $relation = self::$model->{$with}();
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

            $withTable = $relation->getRelated()->getTable();

            $this->repository->join($withTable, "{$withTable}.{$foreignKey}", "{$currentTable}.{$localKey}");
            $this->repository->orderBy("{$withTable}.{$key}", $sortD);
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

        foreach ($where as $whr) {
            if (strpos($whr['key'], '.') > 0) {
                $this->setWhereHasClause($whr);
                continue;
            } elseif (! in_array($whr['key'], $tableColumns)) {
                continue;
            }

            $this->setQueryBuilderWhereStatement($this->repository, $whr['key'], $whr);
        }
    }

    /**
     * parses out custom method filters etc.
     *
     * @param mixed $request
     */
    protected function parseMethodParams($request): void
    {
        foreach ($this->getAllowedScopes() as $scope) {
            if ($request->has(Helpers::snake($scope))) {
                call_user_func([$this->repository, $scope], $request->get(Helpers::snake($scope)));
            }
        }
    }

    protected function setWhereHasClause(array $where): void
    {
        [$with, $key] = explode('.', $where['key']);

        $sub = self::$model->{$with}()->getRelated();
        $fields = $this->getTableColumns($sub);

        if (! in_array($key, $fields)) {
            return;
        }
        $subKey = $sub->qualifyColumn($key);

        $this->repository->whereHas($with, function ($q) use ($where, $key, $subKey) {

               // $q->select("$key as $subKey");
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
                return;
            case 'NotIn':
                if (! empty($where['values'])) {
                    $query->whereNotIn($key, $where['values']);
                }
                return;
            case 'Basic':
                $query->where($key, $where['operator'], $where['value']);
        }
    }

    /**
     * Gets our default fields for our query.
     *
     * @return array
     */
    protected function getDefaultFields(): array
    {
        return (method_exists($this->resourceSingle, 'getDefaultFields')) ? ($this->resourceSingle)::getDefaultFields() : ['*'];
    }

    /**
     * Gets the allowed scopes for our query.
     *
     * @return array
     */
    protected function getAllowedScopes(): array
    {
        return (method_exists($this->resourceSingle, 'getAllowedScopes')) ? ($this->resourceSingle)::getAllowedScopes() : [];
    }

    /**
     * parses the fields to return.
     *
     * @return array
     */
    protected function parseFieldParams(): array
    {
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->getDefaultFields()); //$this->getFieldParamSets();

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
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->getDefaultFields());

        foreach ($fields as $key => $field) {
            if (strpos($field, $include.'.') === false) {
                unset($fields[$key]);

                continue;
            }
            $fields[$key] = str_replace($include.'.', '', $field);
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
