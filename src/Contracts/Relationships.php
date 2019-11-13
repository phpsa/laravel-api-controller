<?php

namespace Phpsa\LaravelApiController\Contracts;

use Phpsa\LaravelApiController\Exceptions\ApiException;

trait Relationships
{
    /**
     * Holds list of allowed include parameters.
     *
     * @var array
     */
    protected static $allowedIncludes = [];

    /**
     * parses the whitelist and blacklist of includes if set
     * and mapps to the allowedIncludes static param.
     */
    protected function parseIncludesMap(): void
    {
        if (! empty($this->includesWhitelist)) {
            foreach ($this->includesWhitelist as $include) {
                self::$allowedIncludes[$include] = true;
            }
        }

        if (! empty($this->includesBlacklist)) {
            foreach ($this->includesBlacklist as $include) {
                self::$allowedIncludes[$include] = false;
            }
        }
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
        return array_filter($includes, static function ($item) {
            $callable = method_exists(self::$model, $item);

            if (! $callable) {
                return false;
            }

            if (empty(self::$allowedIncludes)) {
                return true;
            }

            return isset(self::$allowedIncludes[$item]) && self::$allowedIncludes[$item] === true;
        });
    }

    protected function storeRelated($item, $relateds, $data): void
    {
        if (empty($relateds)) {
            return;
        }

        $filteredRelateds = $this->filterAllowedIncludes($relateds);

        foreach ($filteredRelateds as $with) {
            $relation = $item->$with($data[$with]);
            $this->repository->with($with);
            $type = class_basename(get_class($relation));

            $foreignKey = $relation->getForeignKeyName();
            $localKey = $relation->getLocalKeyName();
            dd($foreignKey, $localKey, $item, $data[$with]);



            switch ($type) {
                case 'HasMany':
                    $relation->createMany($data[$with]);
                    break;
                case 'HasOne':
                    if(isset($data[$with]));
                    $relation->create($data[$with]);
                    break;
                default:
                    throw new ApiException("$type mapping not implemented yet");
            }
        }
    }
}
