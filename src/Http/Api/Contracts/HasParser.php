<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Collection;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\UriParser;

trait HasParser
{
    /**
     * UriParser instance.
     *
     * @var \Phpsa\LaravelApiController\UriParser
     */
    protected $uriParser;

    protected $originalQueryParams;


    protected function getUriParser()
    {

        if (is_null($this->uriParser)) {
            $this->uriParser = new UriParser($this->request, config('laravel-api-controller.parameters.filter'));
        }

        return $this->uriParser;
    }

    /**
     * Method to add extra request parameters to the request instance.
     *
     * @param mixed $request
     * @param array $extraParams
     */
    protected function addCustomParams(array $extraParams = []): void
    {
        $this->originalQueryParams = $this->request->query();

        $all = $this->request->all();
        $new = Helpers::array_merge_request($all, $extraParams);
        $this->request->replace($new);
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

            $this->builder->orderBy($sortF, $sortD);
        }

        if ($withSorts->count() > 0) {
            $this->parseJoinSorts($withSorts);
        }
    }

    protected function parseJoinSorts(Collection $sorts)
    {
        $currentTable = self::$model->getTable();

        $fields = array_map(function ($field) use ($currentTable) {
            return $currentTable . '.' . $field;
        }, $this->parseFieldParams());

        $this->builder->select($fields);

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

            $withTableName = strpos($withTable, '.') === false ? $withConnection . '.' . $withTable : $withTable;

            $this->builder->leftJoin($withTableName, "{$withTableName}.{$foreignKey}", "{$currentTable}.{$localKey}");
            $this->builder->orderBy("{$withTableName}.{$key}", $sortD);
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
        $where = $this->uriParser->whereParameters();

        if (empty($where)) {
            return;
        }

        /** @scrutinizer ignore-call */
        $tableColumns = $this->getTableColumns();
        $table = self::$model->getTable();

        foreach ($where as $whr) {
            if (strpos($whr['key'], '.') > 0) {
                $this->/** @scrutinizer ignore-call */setWhereHasClause($whr);
                continue;
            } elseif (! in_array($whr['key'], $tableColumns)) {
                continue;
            }
            $this->/** @scrutinizer ignore-call */setQueryBuilderWhereStatement($this->builder, $table . '.' . $whr['key'], $whr);
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
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->
        /** @scrutinizer ignore-call */
        getDefaultFields());

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
