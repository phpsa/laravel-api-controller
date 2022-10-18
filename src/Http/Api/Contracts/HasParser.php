<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\UriParser;

/**
 * @property \Illuminate\Http\Request $request
 * @property string|array<string,string>|null $parentModel
 */
trait HasParser
{
    /**
     * UriParser instance.
     *
     * @var \Phpsa\LaravelApiController\UriParser
     */
    protected ?UriParser $uriParser = null;

    protected $originalQueryParams;


    protected function getUriParser(): UriParser
    {

        if (is_null($this->uriParser)) {
            $this->uriParser = new UriParser($this->request, config('laravel-api-controller.parameters.filter'));
        }

        return $this->uriParser;
    }

    /**
     * Method to add extra request parameters to the request instance.
     *
     * @param array $extraParams
     */
    protected function addCustomParams(array $extraParams = []): void
    {
        $this->originalQueryParams = $this->request->query();

        $all = $this->request->all();
        $new = Helpers::array_merge_request($all, $extraParams, $this->filterByParent());
        $this->request->replace($new);
    }


    protected function filterByParent(): array
    {
        $parent = $this->parentModel ?? null;
        if ($parent === null) {
            return [];
        }

        if (is_array($parent)) {
            $key = key($parent);
            $param = strtolower(class_basename(reset($parent)));
        } else {
            $key = strtolower(class_basename($parent));
            $param = $key;
        }

        $routeRelation = $this->request->route()->parameter($param);

        $child = resolve($this->model());

        if (! $routeRelation instanceof Model) {
            $bindingField = $this->request->route()->bindingFieldFor($param) ?? $child->{$key}()->getRelated()->getKeyName();
            $routeRelation = $child->{$key}()->getRelated()->where($bindingField, $routeRelation)->firstOrFail();
        }

        $parentPolicy = Gate::getPolicyFor($routeRelation);

        if (! is_null($parentPolicy)) {
            $this->authorize('view', $routeRelation);
        }

        $filter = match (class_basename(get_class($child->{$key}()))) {
            'HasOne' => $child->{$key}()->getLocalKeyName(),
            'BelongsToMany' => $key . '.' . $child->{$key}()->getRelatedKeyName(),
            default => $child->{$key}()->getForeignKeyName(),
        };

        if ($this->request->isMethod('get') || $this->request->isMethod('options')) {
            return $filterKey ? [
                'filter' => [
                    $filter => $routeRelation->getKey()
                ]
            ] : [] ;
        }

        if ($this->request->isMethod('post') || $this->request->isMethod('put') || $this->request->isMethod('patch')) {
            return [
                $filter => $routeRelation->getKey()
            ];
        }

        return [];
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

            $this->getBuilder()->orderBy($sortF, $sortD);
        }

        if ($withSorts->count() > 0) {
            $this->parseJoinSorts($withSorts);
        }
    }

    protected function parseJoinSorts(Collection $sorts)
    {
        $currentTable = $this->getModel()->getTable();

        $fields = array_map(function ($field) use ($currentTable) {
            return $currentTable . '.' . $field;
        }, $this->parseFieldParams());

        $this->getBuilder()->select($fields);

        foreach ($sorts as $sortF => $sortD) {
            [$with, $key] = explode('.', $sortF);
            $relation = $this->getModel()->{Helpers::camel($with)}();
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

            $this->getBuilder()->leftJoin($withTableName, "{$withTableName}.{$foreignKey}", "{$currentTable}.{$localKey}");
            $this->getBuilder()->orderBy("{$withTableName}.{$key}", $sortD);
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
        $table = $this->getModel()->getTable();

        foreach ($where as $whr) {
            if (strpos($whr['key'], '.') > 0) {
                $this->/** @scrutinizer ignore-call */setWhereHasClause($whr);
                continue;
            } elseif (! in_array($whr['key'], $tableColumns)) {
                continue;
            }
            $this->/** @scrutinizer ignore-call */setQueryBuilderWhereStatement($this->getBuilder(), $table . '.' . $whr['key'], $whr);
        }
    }



    /**
     * parses the fields to return.
     *
     * @return array
     */
    protected function parseFieldParams(): array
    {
        $default = $this->getDefaultFields();
        if ($default !== ['*']) {
            $default = array_merge($default, $this->getAlwaysSelectFields());
        }

        $fields = Helpers::filterFieldsFromRequest(
            $this->request,
            $default
        );

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
        $limit = $this->request->has($limitField) ? intval($this->request->input($limitField)) : $this->getDefaultLimit();

        if ($this->maximumLimit && ($limit > $this->maximumLimit || ! $limit)) {
            $limit = $this->maximumLimit;
        }

        return $limit;
    }

    protected function getDefaultLimit(): ?int
    {
        return $this->defaultLimit ?? $this->getModel()->getPerPage();
    }
}
