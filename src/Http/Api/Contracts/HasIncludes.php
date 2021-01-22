<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Str;
use Phpsa\LaravelApiController\Helpers;

trait HasIncludes
{

    protected $includesWhitelist = [];

    protected $includesBlacklist = [];

    /**
     * Gets whitelisted methods.
     *
     * @return array
     */
    protected function getIncludesWhitelist(): array
    {
        return is_array($this->includesWhitelist) ? $this->includesWhitelist : [];
    }

    /**
     * Gets blacklisted methods.
     *
     * @return array
     */
    protected function getIncludesBlacklist(): array
    {
        return is_array($this->includesBlacklist) ? $this->includesBlacklist : [];
    }

    /**
     * is method blacklisted.
     *
     * @param string $item
     *
     * @return bool
     */
    public function isBlacklisted($item)
    {
        return in_array($item, $this->getIncludesBlacklist()) || $this->getIncludesBlacklist() === ['*'];
    }

    /**
     * filters the allowed includes and returns only the ones that are allowed.
     *
     * @param array $includes
     *
     * @return array
     */
    protected function filterAllowedIncludes(array $includes): array
    {
        return array_filter(Helpers::camelCaseArray($includes), function ($item) {
            $callable = method_exists(self::$model, $item);

            if (! $callable) {
                return false;
            }

            //check if in the allowed includes array:
            if (in_array($item, Helpers::camelCaseArray($this->getIncludesWhitelist()))) {
                return true;
            }

            if ($this->isBlacklisted($item) || $this->isBlacklisted(Helpers::snake($item))) {
                return false;
            }

            return empty($this->getIncludesWhitelist()) && ! Str::startsWith($item, '_');
        });
    }

      /**
     * Parses our include joins.
     */
    protected function parseIncludeParams(): void
    {
        $field = config('laravel-api-controller.parameters.include', 'include');

        $includes = $this->request->input($field);

        if (empty($includes)) {
            return;
        }

        $withs = array_flip(
            $this->
            /** @scrutinizer ignore-call */
            filterAllowedIncludes(explode(',', $includes))
        );

        foreach ($withs as $with => $idx) {
            /** @scrutinizer ignore-call */
            $sub = $this->getRelatedModel($with);
            $fields = $this->getIncludesFields($with);

            if (! empty($fields)) {
                $fields[] = $sub->getKeyName();
            }

            $withs[$with] = $this->setWithQuery($this->mapWith($with, $sub), $fields);
        }

        $this->builder->with($withs);
    }

    protected function mapWith(string $with, $sub): array
    {

        $where = array_filter($this->uriParser->whereParameters(), function ($where) use ($with) {
            return strpos($where['key'], Helpers::snake($with) . '.') !== false;
        });

        return array_map(function ($whr) use ($with, $sub) {
                $key = str_replace(Helpers::snake($with) . '.', '', $whr['key']);
                $whr['key'] = $sub->qualifyColumn($key);

                return $whr;
        }, $where);
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
        $fields = Helpers::filterFieldsFromRequest($this->request, $this->
        /** @scrutinizer ignore-call */
        getDefaultFields());

        $type = $this->getRelatedModel($include);

        /** @scrutinizer ignore-call */
        $tableColumns = $this->getTableColumns($type);

        foreach ($fields as $key => $field) {
            $parts = explode('.', $field);
            if (strpos($field, Helpers::snake($include) . '.') === false || ! in_array(end($parts), $tableColumns)) {
                unset($fields[$key]);

                continue;
            }

            $fields[$key] = str_replace(Helpers::snake($include) . '.', '', $field);
        }

        return $fields;
    }
}
