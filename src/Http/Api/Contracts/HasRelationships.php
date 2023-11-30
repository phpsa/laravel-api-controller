<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Phpsa\LaravelApiController\Exceptions\ApiException;
use Phpsa\LaravelApiController\Helpers;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

trait HasRelationships
{
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

            $relation = resolve($this->model())->$with();

            if ($relation instanceof Relation === false) {
                if (is_null($relation)) {
                    throw new LogicException(sprintf(
                        '%s::%s must return a relationship instance, but "null" was returned. Was the "return" keyword used?', $this->model(), $with
                    ));
                }

                throw new LogicException(sprintf(
                    '%s::%s must return a relationship instance.', $this->model(), $with
                ));
            }

            // We now know that $with is a relation that exists on the model,
            // so we are safe to … ?
            $relation = $item->$with();

            $type = class_basename(get_class($relation));
            $relatedRecords = $data[Helpers::snake($with)];

            if($relatedRecords === null){
                continue;
            }

            $this->getBuilder()->with($with);

            switch ($type) {
                case 'HasOne':
                case 'MorphOne':
                    $this->processHasOneRelation($relation, $relatedRecords);
                    break;
                case 'HasMany':
                case 'MorphMany':
                    $this->processHasRelation($relation, $relatedRecords, $item, $with);
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
            $item->wasRecentlyCreated = true;
            $item->unsetRelation($with)->getRelationValue($with);
        }
    }

    protected function processHasOneRelation(HasOne|MorphOne $relation, array $data): void
    {
        $relatedRecord = $relation->getResults() ?? $relation->make();
        $relatedRecord->fill($data)->save();
    }

    protected function processHasRelation($relation, array $relatedRecords, $item, string $with): void
    {
        $localKey = $relation->getLocalKeyName();
        $foreignKey = $relation->getForeignKeyName();

        foreach ($relatedRecords as $relatedRecord) {
            $model = $relation->getRelated();

            $relatedRecord[$foreignKey] = $item->getAttribute($localKey);

            if (isset($relatedRecord[$localKey])) {
                $existanceCheck = [$localKey => $relatedRecord[$localKey]];

                $item->{$with}()->updateOrCreate($existanceCheck, $relatedRecord);
            } else {
                $item->{$with}()->create($relatedRecord);
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
            $relatedRecordData = collect($relatedRecord)->except($pivots)->toArray();
            if (! isset($relatedRecord[$parentKey])) {
                $relatedRecord[$parentKey] = $item->getAttribute($relatedKey);
            }
            if ($relatedRecord[$parentKey]) {
                $existanceCheck = [$parentKey => $relatedRecord[$parentKey]];
                $record = $model->updateOrCreate($existanceCheck, $relatedRecordData);
            } elseif (isset($data[$relatedKey])) {
                $existanceCheck = [$parentKey => $data[$relatedKey]];
                $record = $model->updateOrCreate($existanceCheck, $relatedRecordData);
            } else {
                $record = $model->create($relatedRecordData);
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
