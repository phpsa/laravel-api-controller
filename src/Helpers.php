<?php

namespace Phpsa\LaravelApiController;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Helpers
{
    /**
     * CamelCases an array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function camelCaseArray(array $array): array
    {
        return array_map(function ($value) {
            return self::camel($value);
        }, $array);
    }

    /**
     * Snake cases an array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function snakeCaseArray(array $array): array
    {
        return array_map(function ($value) {
            return self::snake($value);
        }, $array);
    }

    /**
     * camel cases array keys.
     *
     * @param array $array
     *
     * @return array
     */
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


    public static function studlyCaseArrayKeys(array $array): array
    {
        $keys = array_keys($array);
        foreach ($keys as $key) {
            $value = &$array[$key]; //reference and not copy so that keeps any modifiers
            unset($array[$key]);

            if (is_array($value)) {
                $value = self::studlyCaseArrayKeys($value);
            }

            $newKey = Str::of($key)->studly()->ucfirst()->toString();
            $array[$newKey] = $value;
            unset($value); //cleanup
        }

        return $array;
    }

    /**
     * Snake cases array keys.
     *
     * @param array $array
     *
     * @return array
     */
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

    /**
     * Convert to snake.
     *
     * @param string $value
     *
     * @return string
     */
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

    public static function studly($value)
    {
        // Preserve all caps
        if (strtoupper($value) === $value) {
            return $value;
        }

        return Str::studly($value);
    }


    protected static function filterExtraFields(array $fields, ?string $fieldKey): array
    {
        return collect($fields)->when(
            $fieldKey === null,
            fn($collection) => $collection->filter(fn($str) => ! Str::of($str)->contains(".")),
            fn($collection) => $collection->filter(fn($str) => Str::of($str)->startsWith($fieldKey))
        )->when(
            $fieldKey !== null,
            fn($collection) => $collection->map(
                fn($str) => Str::of($str)->replace($fieldKey, "")->toString()
            )
        )->toArray();
    }

    protected static function fetchRequestFieldValues($request, string $field, ?string $fieldKey = null): ?array
    {
        if (! $request->has($field)) {
            return null;
        }
        return self::filterExtraFields(explode(",", $request->input($field)), $fieldKey);
    }
    /**
     * Combines,defaults, added, excluded and specifically set field params.
     *
     * @return array
     */
    public static function filterFieldsFromRequest($request, ?array $defaultFields, ?array $allowedFields = [], ?string $fieldKey = null): array
    {
        $config = config('laravel-api-controller.parameters');
        $fieldParam = $config['fields'] ?? 'fields';
        $addFieldParam = $config['addfields'] ?? 'addfields';
        $removeFieldParam = $config['removefields'] ?? 'removefields';
        $includeFieldParam = $config['include'] ?? 'include';

        $fields = self::fetchRequestFieldValues($request, $fieldParam, $fieldKey) ?? $defaultFields ?? [];

        //extra fields
        $extra = self::fetchRequestFieldValues($request, $addFieldParam, $fieldKey) ?? [];
        $fields = array_merge($fields, $extra);

        //include fields
        $extra = self::fetchRequestFieldValues($request, $includeFieldParam, $fieldKey) ?? [];
        $fields = array_merge($fields, $extra);

        //put || post
        $fields = array_merge($fields, self::fieldsFromPutPost($request, $fields));

        $excludes =  self::fetchRequestFieldValues($request, $removeFieldParam, $fieldKey) ?? [];

        $remaining = self::excludeArrayValues($fields, $excludes, $allowedFields);

        return array_unique($remaining);
    }

    protected static function fieldsFromPutPost($request, $fields): array
    {
        $method = $request->method();
        if (! in_array($method, ['PUT', 'POST', 'PATCH'])) {
            return [];
        }

        return array_values(collect($request->all())->filter(function ($item, $key) use ($fields) {
            if (in_array($key, $fields)) {
                return false;
            }

            return true;
        })->map(function ($item, $key) {
            return $key;
        })->toArray());
    }

    /**
     * method to remove array values.
     *
     * @param array $array
     * @param array $excludes
     *
     * @return array
     */
    public static function excludeArrayValues(array $array, array $excludes, ?array $acceptable = []): array
    {
        return Arr::where($array, function ($value) use ($excludes, $acceptable) {
            if (in_array($value, $excludes)) {
                return false;
            }

            if (! empty($acceptable)) {
                return in_array($value, $acceptable);
            }

            return true;
        });
    }

    public static function array_merge_request($main, ...$arrays): array
    {
        foreach ($arrays as $array) {
            $main = self::array_merge_replace($main, $array);
        }

        return $main;
    }

    public static function array_merge_replace(array $array, array $newValues): array
    {
        foreach ($newValues as $key => $value) {
            if (is_array($value)) {
                if (! isset($array[$key])) {
                    $array[$key] = [];
                }
                $array[$key] = self::array_merge_replace($array[$key], $value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }
}
