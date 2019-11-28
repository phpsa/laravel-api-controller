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
    protected function mapFields($request): array
    {
        $allowedFields = static::$allowedFields ?? array_keys($this->resource->getAttributes());
        $fields = Helpers::filterFieldsFromRequest($request, $allowedFields);

        return array_intersect($allowedFields, $fields);
    }
}
