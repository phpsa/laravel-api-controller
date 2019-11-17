<?php

namespace Phpsa\LaravelApiController;
use Illuminate\Support\Str;

class Helpers
{

    public static function camelCaseArrayKeys(array $array): array
    {
        $keys = array_keys($array);
        foreach($keys as $key){
            $value = &$array[$key]; //reference and not copy so that keeps any modifiers
            unset($array[$key]);

            if (is_array($value) ) {
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

            $newKey = self::camel($key);
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
     * Note this is generally only applicable when dealing with API input/output key case
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

}