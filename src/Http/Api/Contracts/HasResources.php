<?php
namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Phpsa\LaravelApiController\Http\Resources\ApiResource;
use Phpsa\LaravelApiController\Http\Resources\ApiCollection;

trait HasResources
{

    /**
     * Resource for item.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\JsonResource
     */
    protected $resourceSingle = ApiResource::class;

    /**
     * Resource for collection.
     *
     * @var mixed instance of \Illuminate\Http\Resources\Json\ResourceCollection
     */
    protected $resourceCollection = ApiCollection::class;

    /**
     * Gets the single resource used in this endpoint
     *
     * @return ApiResource
     */
    public function getResourceSingle()
    {
        return $this->resourceSingle ?? \Phpsa\LaravelApiController\Http\Resources\ApiResource::class;
    }

    /**
     * gets the collection resource used in this endpoint
     *
     * @return ApiCollection
     */
    public function getResourceCollection()
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
