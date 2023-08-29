<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Http\Request;
use Phpsa\LaravelApiController\Exceptions\ApiException;


trait HasValidation
{

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
