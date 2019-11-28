<?php

namespace Phpsa\LaravelApiController\Contracts;

use Illuminate\Support\Str;
use Phpsa\LaravelApiController\Exceptions\ApiException;

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
        return array_filter($includes, function ($item) {
            $callable = method_exists(self::$model, $item);

            if (! $callable) {
                return false;
            }

            //check if in the allowed includes array:
            if (in_array($item, $this->getIncludesWhitelist())) {
                return true;
            }

            if ($this->isBlacklisted($item)) {
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

            if (! in_array($type, ['HasOne', 'HasMany'])) {
                throw new ApiException("$type mapping not implemented yet");
            }

            $collection = $type === 'HasOne' ? [$data[$with]] : $data[$with];
            $this->repository->with($with);
            $localKey = $relation->getLocalKeyName();

            foreach ($collection as $relatedRecord) {
                if (isset($relatedRecord[$localKey])) {
                    $existanceCheck = [$localKey => $relatedRecord[$localKey]];
                    $item->$with()->updateOrCreate($existanceCheck, $relatedRecord);
                } else {
                    $item->$with()->create($relatedRecord);
                }
            }
        }
    }
}
