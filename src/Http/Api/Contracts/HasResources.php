<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\Http\Resources\ApiCollection;
use Phpsa\LaravelApiController\Http\Resources\ApiResource;

trait HasResources
{
    /**
     * Resource for item.
     *
     * @var string
     */
    protected $resourceSingle = ApiResource::class;

    /**
     * Resource for collection.
     *
     * @var string
     */
    protected $resourceCollection = ApiCollection::class;

    /**
     * Gets the single resource used in this endpoint.
     *
     * @return string
     */
    public function getResourceSingle(): string
    {
        return $this->resourceSingle;
    }

    /**
     * gets the collection resource used in this endpoint.
     *
     * @return string
     */
    public function getResourceCollection(): string
    {
        return $this->resourceCollection;
    }

    /**
     * Gets our default fields for our query.
     *
     * @return array
     */
    protected function getDefaultFields(): array
    {
        $resource = $this->getResourceSingle();

        return (method_exists($resource, 'getDefaultFields')) ? ($resource)::getDefaultFields($this->request) : ['*'];
    }

    /**
     * Gets our fields that should always be included in the select query
     *
     * @return array<int, string>
     */
    protected function getAlwaysSelectFields(): array
    {
        $resource = $this->getResourceSingle();

        return (method_exists($resource, 'getAlwaysSelectFields')) ? ($resource)::getAlwaysSelectFields() : [];
    }

    /**
     * Gets the allowed scopes for our query.
     *
     * @return array
     */
    protected function getAllowedScopes(): array
    {
        $resource = $this->getResourceSingle();

        $scopes = collect((method_exists($resource, 'getAllowedScopes')) ? ($resource)::getAllowedScopes($this->request) : []);

        return $scopes->map(function ($scope) {
            return strpos($scope, 'scope') === 0 ? substr($scope, 5) : $scope;
        })->toArray();
    }

    /**
     * parses out custom method filters etc.
     *
     */
    protected function parseAllowedScopes(): void
    {
        foreach ($this->getAllowedScopes() as $scope) {
            $snake = Helpers::snake($scope);
            $camel = Helpers::camel($scope);

            if ($this->request->has($snake) || $this->request->has($camel)) {
                $value = $this->parseScopeValue($this->request->has($snake) ? $this->request->get($snake) : $this->request->get($camel));
                call_user_func([$this->getBuilder(), $camel], $value);
            }
        }
    }

    /**
     * Parse the value to string / array based in input.
     *
     * @param string|array|null $value
     *
     * @return string|array|null
     */
    protected function parseScopeValue($value = null)
    {
        if ($value === null || is_array($value) || strpos($value, '||') === false) {
            return $value;
        }

        return explode('||', $value);
    }
}
