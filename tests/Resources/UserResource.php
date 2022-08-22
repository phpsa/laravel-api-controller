<?php

namespace Phpsa\LaravelApiController\Tests\Resources;

use Phpsa\LaravelApiController\Http\Resources\ApiResource;
use Phpsa\LaravelApiController\Tests\Resources\UserProfileResource;

class UserResource extends ApiResource
{

    /**
     * Resources to be mapped (ie children).
     *
     * @var array|null
     */
    protected static $mapResources = [
        'profile' => UserProfileResource::class,
    ];

    /**
     * Default fields to return on request.
     *
     * @var array|null
     */
    protected static $defaultFields = [
        'name',
        'email',
    ];

    /**
     * Allowable fields to be used.
     *
     * @var array|null
     */
    protected static $allowedFields = null;

    /**
     * Allowable scopes to be used.
     *
     * @var array|null
     */
    protected static $allowedScopes = [
        'scopeHas2Fa'
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
    ];
}
