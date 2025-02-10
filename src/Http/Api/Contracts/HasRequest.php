<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

trait HasRequest
{

    protected bool $onlyValidated = false;

    /**
     * \Illuminate\Http\Request instance.
     *
     * @var mixed|\Illuminate\Http\Request | \Illuminate\Foundation\Http\FormRequest
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


    protected function getRequestArray(): array
    {
        return $this->onlyValidated && is_a($this->request, FormRequest::class) ? $this->request->validated() : $this->request->all();
    }

    protected function setOnlyValidated(bool $option = true): self
    {
        $this->onlyValidated = $option;
        return $this;
    }
}
