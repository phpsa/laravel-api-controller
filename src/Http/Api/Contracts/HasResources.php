<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

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
        return $this->resourceSingle ?? \Phpsa\LaravelApiController\Http\Resources\ApiResource::class;
    }

    /**
     * gets the collection resource used in this endpoint.
     *
     * @return string
     */
    public function getResourceCollection(): string
    {
        return $this->resourceCollection ?? \Phpsa\LaravelApiController\Http\Resources\ApiCollection::class;
    }

    /**
     * Gets our default fields for our query.
     *
     * @return array
     */
    protected function getDefaultFields(): array
    {
        $resource = $this->getResourceSingle();

        return (method_exists($resource, 'getDefaultFields')) ? ($resource)::getDefaultFields() : ['*'];
    }

    /**
     * Gets the allowed scopes for our query.
     *
     * @return array
     */
    protected function getAllowedScopes(): array
    {
        $resource = $this->getResourceSingle();

        return (method_exists($resource, 'getAllowedScopes')) ? ($resource)::getAllowedScopes() : [];
    }
}
