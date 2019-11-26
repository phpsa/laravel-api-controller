<?php

namespace Phpsa\LaravelApiController\Contracts;

use Illuminate\Http\Request;
use Phpsa\LaravelApiController\Exceptions\ApiException;

trait Validation
{
    /**
     * \Illuminate\Http\Request instance.
     *
     * @var mixed|\Illuminate\Http\Request | \Illuminate\Foundation\Http\FormRequest;
     */
    protected $request;

    /**
     * validates that the request is of a request type.
     *
     * @param mixed $request
     *
     * @throws ApiException
     */
    protected function validateRequestType($request): void
    {
        if (! is_a($request, Request::class)) {
            throw new ApiException(
                "Request should be an instance of \Illuminate\Http\Request || \Illuminate\Foundation\Http\FormRequest"
            );
        }

        $this->request = $request;
    }

    /**
     * Get the validation rules for create.
     *
     * @return array
     */
    protected function rulesForCreate(): array
    {
        return [];
    }

    /**
     * Get the validation rules for update.
     *
     * @param int $id
     *
     * @return array
     */
    protected function rulesForUpdate(/* @scrutinizer ignore-unused */$id): array
    {
        return [];
    }

    /**
     * Check if the user has one or more roles.
     *
     * @param mixed $role role name or array of role names
     *
     * @return bool
     */
    protected function hasRole($role): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole((array) $role);
    }

    /**
     * Checks if user has all the passed roles.
     *
     * @param array $roles
     *
     * @return bool
     */
    protected function hasAllRoles(array $roles): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole($roles, true);
    }
}
