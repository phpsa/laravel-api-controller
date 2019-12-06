<?php

namespace Phpsa\LaravelApiController\Contracts;

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

    protected function getUriParser($request)
    {
        if (is_null(self::$uriParser)) {
            self::$uriParser = new UriParser($request, config('laravel-api-controller.parameters.filter'));
        }

        return self::$uriParser;
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
        $withs = $this->filterAllowedIncludes($withs);

        foreach ($withs as $idx => $with) {
            $sub = self::$model->{$with}()->getRelated();
            $fields = $this->getIncludesFields($with);

            if (! empty($fields)) {
                $fields[] = $sub->getKeyName();
                $withs[$idx] = $with.':'.implode(',', array_unique($fields));
            }
        }

        $this->repository->with($withs);
    }

    /**
     * Parses our sort parameters.
     */
    protected function parseSortParams(): void
    {
        $sorts = $this->getSortValue();

        foreach ($sorts as $sort) {
            $sortP = explode(' ', $sort);
            $sortF = $sortP[0];

            /** @scrutinizer ignore-call */
            $tableColumns = $this->getTableColumns();

            if (empty($sortF) || ! in_array($sortF, $tableColumns)) {
                continue;
            }

            $sortD = ! empty($sortP[1]) && strtolower($sortP[1]) === 'desc' ? 'desc' : 'asc';
            $this->repository->orderBy($sortF, $sortD);
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
                //@TODO: test if exists in the withs, if not continue out to exclude from the qbuild
                //continue;
            } elseif (! in_array($whr['key'], $tableColumns)) {
                continue;
            }

            $this->setWhereClause($whr);
        }
    }

    /**
     * set the Where clause.
     *
     * @param array $where the where clause
     */
    protected function setWhereClause($where): void
    {
        switch ($where['type']) {
            case 'In':
                if (! empty($where['values'])) {
                    $this->repository->whereIn($where['key'], $where['values']);
                }
                break;
            case 'NotIn':
                if (! empty($where['values'])) {
                    $this->repository->whereNotIn($where['key'], $where['values']);
                }
                break;
            case 'Basic':
                $this->repository->where($where['key'], $where['value'], $where['operator']);
                break;
        }
    }

    /**
     * parses the fields to return.
     *
     * @return array
     */
    protected function parseFieldParams(): array
    {
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->defaultFields); //$this->getFieldParamSets();

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
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->defaultFields);

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
