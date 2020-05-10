<?php

namespace Phpsa\LaravelApiController\Contracts;

use Illuminate\Support\Str;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Helpers;

trait Relationships
{
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
     * Method used to store related.
     *
     * @param mixed $item newly created \Illuminate\Database\Eloquent\Model instance
     * @param array $includes
     * @param array $data
     */
    protected function storeRelated($item, array $includes, array $data): void
    {
        if (empty($includes)) {
            return;
        }

        $filteredRelateds = $this->filterAllowedIncludes($includes);

        foreach ($filteredRelateds as $with) {
            $relation = $item->$with();
            $type = class_basename(get_class($relation));

            if (! in_array($type, ['HasOne', 'HasMany', 'BelongsTo', 'BelongsToMany'])) {
                throw new ApiException("$type mapping not implemented yet");
            }

            $collection = $data[Helpers::snake($with)];

            $this->repository->with($with);

            switch ($type) {
                case 'HasOne':
                    $this->processHasOneRelation($relation, $collection, $item);
                break;
                case 'HasMany':
                    $this->processHasManyRelation($relation, $collection, $item);
                break;
                case 'BelongsTo':
                    $this->processBelongsToRelation($relation, $collection, $item);
                break;
                case 'BelongsToMany':
                    //This one is most likely in a glue mapping
                    $this->processBelongsToManyRelation($relation, $collection, $item, $data);
                break;

            }
        }
    }

    protected function storeRelatedChild($relatedItem, $data): void
    {
        //$columns = $this->getTableColumns($relatedItem);
        //$insert = array_intersect_key($data, array_flip($columns));
        //$diff = array_diff(array_keys($data), array_keys($insert));
        // then similar to the main methodology
        //@todo
    }

    protected function processHasManyRelation($relation, array $collection, $item): void
    {
        $localKey = $relation->getLocalKeyName();
        $foreignKey = $relation->getForeignKeyName();

        foreach ($collection as $relatedRecord) {
            $model = clone $relation;

            $relatedRecord[$foreignKey] = $item->getAttribute($localKey);
            if (isset($relatedRecord[$localKey])) {
                $existanceCheck = [$localKey => $relatedRecord[$localKey]];
                $model->updateOrCreate($existanceCheck, $relatedRecord);
            } else {
                $model->create($relatedRecord);
            }
        }
    }

    protected function processHasOneRelation($relation, array $collection, $item): void
    {
        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();

        $collection[$foreignKey] = $item->getAttribute($localKey);

        $existanceCheck = [$foreignKey => $item->getAttribute($localKey)];
        $relation->updateOrCreate($existanceCheck, $collection);
    }

    protected function processBelongsToRelation($relation, array $collection, $item): void
    {
        $ownerKey = $relation->getOwnerKeyName();
        $localKey = $relation->getForeignKeyName();

        $current = $item->getAttribute($localKey);

        if ($current) {
            //relation mapping already exists
            $existanceCheck = [$ownerKey => $current];
            $relation->associate(
                $relation->updateOrCreate($existanceCheck, $collection)
            );
        } else {
            $relation->associate(
                $relation->create($item)
            );
            $item->save();
        }
    }

    // This one still needs a bit of work i believe
    protected function processBelongsToManyRelation($relation, array $collection, $item, array $parent): void
    {
        $ownerKey = $relation->getOwnerKeyName();
        $localKey = $relation->getForeignKeyName();

        foreach ($collection as $relatedRecord) {
            $model = clone $relation;

            if (isset($relatedRecord[$ownerKey])) {
                $existanceCheck = [$ownerKey => $relatedRecord[$ownerKey]];
                $model->associate(
                    $model->updateOrCreate($existanceCheck, $relatedRecord)
                );
            } elseif (isset($parent[$localKey])) {
                $existanceCheck = [$ownerKey => $parent[$localKey]];
                $model->associate(
                    $model->updateOrCreate($existanceCheck, $relatedRecord)
                );
            } else {
                $model->associate(
                    $model->create($relatedRecord)
                );
            }
            $item->save();
        }
    }
}
