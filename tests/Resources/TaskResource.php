<?php

namespace Phpsa\LaravelApiController\Tests\Resources;

use Phpsa\LaravelApiController\Http\Resources\ApiResource;

class TaskResource extends ApiResource
{

    /**
     * Resources to be mapped (ie children).
     *
     * @var array|null
     */
    protected static $mapResources = [
    ];

    /**
     * Default fields to return on request.
     *
     * @var array|null
     */
    protected static $defaultFields = [
        'name',
        'completed_at',
    ];

    /**
     * There are times where we need to select specific fields that are required
     * but should not be in the response, ie relationship id or calculated
     * attribute dependencies for display under a different name
     *
     * @var array
     */
    protected static array $alwaysSelectFields = [
        'id',
        'project_id',
    ];
}
