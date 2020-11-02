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
            $relatedRecords = $data[Helpers::snake($with)];

            $this->repository->with($with);

            switch ($type) {
                case 'HasOne':
                case 'MorphOne':
                    $this->processHasOneRelation($relation, $relatedRecords, $item);
                    break;
                case 'HasMany':
                case 'MorphMany':
                    $this->processHasRelation($relation, $relatedRecords, $item);
                    break;
                case 'BelongsTo':
                case 'MorphTo':
                    $this->processBelongsToRelation($relation, $relatedRecords, $item, $data);
                    break;
                case 'BelongsToMany':
                case 'MorphToMany':
                    $this->processBelongsToManyRelation($relation, $relatedRecords, $item, $data, $with);
                    break;
                default:
                    throw new ApiException("$type mapping not implemented yet");
                break;
            }
            $item->load($with);
        }
    }

    protected function processHasOneRelation($relation, array $collection, $item): void
    {
        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();
        if (isset($collection[$foreignKey])) {
            $existanceCheck = [$foreignKey => $collection[$foreignKey]];
        } else {
            $collection[$foreignKey] = $item->getAttribute($localKey);
            $existanceCheck = [$foreignKey => $item->getAttribute($localKey)];
        }
        $relation->updateOrCreate($existanceCheck, $collection);
    }

    protected function processHasRelation($relation, array $relatedRecords, $item): void
    {
        $localKey = $relation->getLocalKeyName();
        $foreignKey = $relation->getForeignKeyName();

        foreach ($relatedRecords as $relatedRecord) {
            $model = $relation->getRelated();

            $relatedRecord[$foreignKey] = $item->getAttribute($localKey);

            if (isset($relatedRecord[$localKey])) {
                $existanceCheck = [$localKey => $relatedRecord[$localKey]];

                $model->updateOrCreate($existanceCheck, $relatedRecord);
            } else {
                $model->create($relatedRecord);
            }
        }
    }

    protected function processBelongsToRelation($relation, $relatedRecord, $item, array $data): void
    {
        $ownerKey = $relation->getOwnerKeyName();
        $localKey = $relation->getForeignKeyName();

        $model = $relation->getRelated();

        if (! isset($relatedRecord[$ownerKey])) {
            $relatedRecord[$ownerKey] = $item->getAttribute($localKey);
        }
        if ($relatedRecord[$ownerKey]) {
            $existanceCheck = [$ownerKey => $relatedRecord[$ownerKey]];
            $relation->associate(
                $model->updateOrCreate($existanceCheck, $relatedRecord)
            );
        } elseif (isset($data[$localKey])) {
            $existanceCheck = [$ownerKey => $data[$localKey]];
            $relation->associate(
                $model->updateOrCreate($existanceCheck, $relatedRecord)
            );
        } else {
            $relation->associate(
                $model->create($relatedRecord)
            );
        }
        $item->save();
    }

    protected function processBelongsToManyRelation($relation, array $relatedRecords, $item, array $data, $with): void
    {
        $parentKey = $relation->getParentKeyName();
        $relatedKey = $relation->getRelatedKeyName();

        $model = $relation->getRelated();
        $pivots = $relation->getPivotColumns();

        $syncWithRelated = Helpers::snakeCaseArrayKeys(request()->get('sync') ?? []);
        $detach = filter_var($syncWithRelated[Helpers::snake($with)] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sync = collect();

        foreach ($relatedRecords as $relatedRecord) {
            if (! isset($relatedRecord[$parentKey])) {
                $relatedRecord[$parentKey] = $item->getAttribute($relatedKey);
            }
            if ($relatedRecord[$parentKey]) {
                $existanceCheck = [$parentKey => $relatedRecord[$parentKey]];
                $record = $model->updateOrCreate($existanceCheck, $relatedRecord);
            } elseif (isset($data[$relatedKey])) {
                $existanceCheck = [$parentKey => $data[$relatedKey]];
                $record = $model->updateOrCreate($existanceCheck, $relatedRecord);
            } else {
                $record = $model->create($relatedRecord);
            }

            $pvals = [];
            if ($pivots) {
                foreach ($pivots as $pivot) {
                    if (isset($relatedRecord[$pivot])) {
                        $pvals[$pivot] = $relatedRecord[$pivot];
                    }
                }
            }
            $sync->put($record->getKey(), $pvals);
        }

        $relation->sync($sync->toArray(), $detach);
    }
}
