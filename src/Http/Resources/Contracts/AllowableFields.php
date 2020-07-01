<?php

namespace Phpsa\LaravelApiController\Http\Resources\Contracts;

use Phpsa\LaravelApiController\Helpers;

trait AllowableFields
{
    /**
     * Resources to be mapped (ie children).
     *
     * @var array|null
     */
    protected static $mapResources = null;

    /**
     * Default fields to return on request.
     *
     * @var array|null
     */
    protected static $defaultFields = null;

    /**
     * Allowable fields to be used.
     *
     * @var array|null
     */
    protected static $allowedFields = null;

    /**
     * Allowable scopes to be used.
     *
     * @var array|null
     */
    protected static $allowedScopes = null;

    /**
     * Makes sure we only return allowable fields.
     *
     * @param mixed $request
     *
     * @return array
     */
    protected function onlyAllowed($request): array
    {
        $fields = Helpers::camelCaseArray($this->mapFields($request));

        $data = parent::toArray($request);

        $resources = array_filter($data, function ($key) use ($fields) {
            return in_array(Helpers::camel($key), $fields);
        }, ARRAY_FILTER_USE_KEY);

        return $this->mapRelatedResources($resources);
    }

    protected function mapRelatedResources($resources)
    {
        if (empty(static::$mapResources)) {
            return $resources;
        }

        foreach ($resources as $key => $value) {
            if (array_key_exists($key, static::$mapResources)) {
                $resources[$key] = static::$mapResources[$key]::make($this->{Helpers::camel($key)});
            }
        }

        return $resources;
    }

    /**
     * Checks for allowed fields.
     *
     * @param mixed $request
     *
     * @return array
     */

    /**
     * Checks for allowed fields.
     *
     * @param mixed $request
     *
     * @return array
     */
    protected function mapFields($request): array
    {
        $map = $this->getDefaultFields();
        $defaultFields = $map === ['*'] ? array_keys($this->getResourceFields()) : $map;
        $allowedFields = static::$allowedFields ?? [];
        $fields = Helpers::filterFieldsFromRequest($request, $defaultFields, $allowedFields);

        return $this->filterAllowedFields($fields);
    }

    public function filterAllowedFields($fields)
    {
        if (empty(static::$allowedFields) || static::$allowedFields === ['*']) {
            return $fields;
        }

        return array_filter($fields, function ($field) {
            return in_array($field, /** @scrutinizer ignore-type */static::$allowedFields);
        });
    }

    /**
     * gets the resource fields as an array.
     *
     * @return array
     */
    protected function getResourceFields(): array
    {
        return is_array($this->resource) ? $this->resource : $this->resource->getAttributes();
    }

    /**
     * Return default fields for this collection.
     *
     * @return array
     */
    public static function getDefaultFields(): array
    {
        return static::$defaultFields ?? ['*'];
    }

    /**
     * Return allowed scopes for this collection.
     *
     * @return array
     * @author Sam Sehnert <sam@customd.com>
     */
    public static function getAllowedScopes(): array
    {
        return static::$allowedScopes ?? [];
    }
}
