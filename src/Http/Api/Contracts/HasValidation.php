<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Http\Request;
use Phpsa\LaravelApiController\Exceptions\ApiException;

trait HasValidation
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
    protected function validateRequestType($request = null): void
    {
        $request = $request ?? request();

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
}
