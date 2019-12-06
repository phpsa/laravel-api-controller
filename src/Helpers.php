<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Helpers
{
    public static function camelCaseArrayKeys(array $array): array
    {
        $keys = array_keys($array);
        foreach ($keys as $key) {
            $value = &$array[$key]; //reference and not copy so that keeps any modifiers
            unset($array[$key]);

            if (is_array($value)) {
                $value = self::camelCaseArrayKeys($value);
            }

            $newKey = self::camel($key);
            $array[$newKey] = $value;
            unset($value); //cleanup
        }

        return $array;
    }

    public static function snakeCaseArrayKeys(array $array): array
    {
        $keys = array_keys($array);
        foreach ($keys as $key) {
            $value = &$array[$key]; //reference and not copy so that keeps any modifiers
            unset($array[$key]);

            if (is_array($value)) {
                $value = self::snakeCaseArrayKeys($value);
            }

            $newKey = self::snake($key);
            $array[$newKey] = $value;
            unset($value); //cleanup
        }

        return $array;
    }

    public static function snake(string $value): string
    {
        if (strtoupper($value) === $value) {
            return $value;
        }
        $value = Str::snake($value);
        // Extra things which Str::snake doesn't do, but maybe should
        $value = str_replace('-', '_', $value);
        $value = preg_replace('/__+/', '_', $value);

        return $value;
    }

    /**
     * Str::camel wrapper - for specific extra functionality
     * Note this is generally only applicable when dealing with API input/output key case.
     *
     * @param string $value
     * @return string
     */
    public static function camel($value)
    {
        // Preserve all caps
        if (strtoupper($value) === $value) {
            return $value;
        }

        return Str::camel($value);
    }

    /**
     * Combines,defaults, added, excluded and specifically set field params.
     *
     * @return array
     */
    public static function filterFieldsFromRequest($request, ?array $defaultFields, ?array $extraFields = []): array
    {
        $config = config('laravel-api-controller.parameters');
        $fieldParam = $config['fields'] ?? 'fields';
        $addFieldParam = $config['addfields'] ?? 'addfields';
        $removeFieldParam = $config['removefields'] ?? 'removefields';
        $includeFieldParam = $config['include'] ?? 'include';

        $defaults = $defaultFields ?? [];

        $fields = $request->has($fieldParam) ? explode(',', $request->input($fieldParam)) : $defaults;

        //extra fields
        $extra = $request->has($addFieldParam) ? explode(',', $request->input($addFieldParam)) : [];
        $fields = array_merge($fields, $extra);

        //include fields
        $extra = $request->has($includeFieldParam) ? explode(',', $request->input($includeFieldParam)) : [];
        $fields = array_merge($fields, $extra);

        $excludes = $request->has($removeFieldParam) ? explode(',', $request->input($removeFieldParam)) : [];
        $remaining = self::excludeArrayValues($fields, $excludes, $extraFields);

        return array_unique($remaining);
    }

    /**
     * method to remove array values.
     *
     * @param array $array
     * @param array $excludes
     *
     * @return array
     */
    public static function excludeArrayValues(array $array, array $excludes, ?array $optionals = []): array
    {
        return Arr::where($array, function ($value) use ($excludes, $optionals) {
            return ! in_array($value, $excludes) || in_array($value, $optionals);
        });
    }
}
