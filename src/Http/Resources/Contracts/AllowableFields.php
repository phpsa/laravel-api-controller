<?php

namespace Phpsa\LaravelApiController\Http\Resources\Contracts;

use Phpsa\LaravelApiController\Helpers;

trait AllowableFields
{
    /**
     * Makes sure we only return allowable fields.
     *
     * @param mixed $request
     *
     * @return array
     */
    protected function onlyAllowed($request): array
    {
        $fields = $this->mapFields($request);

        $data = parent::toArray($request);

        return array_filter($data, function ($key) use ($fields) {
            return in_array($key, $fields);
        }, ARRAY_FILTER_USE_KEY);
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
        $defaultFields = static::$defaultFields ?? array_keys($this->getResourceFields());
        $allowedFields = static::$allowedFields ?? [];

        $fields = Helpers::filterFieldsFromRequest($request, $defaultFields, $allowedFields);

        return array_filter($fields, function ($field) use ($allowedFields) {
            return in_array($field, $allowedFields);
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
}
