<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Phpsa\LaravelApiController\Exceptions\ApiException;

trait HasValidation
{

    /**
     * Get the validation rules for create.
     *
     * @deprecated use FormRequest instead
     * @return array
     */
    protected function rulesForCreate(): array
    {
        return [];
    }

    /**
     * Get the validation rules for update.
     *
     * @param int|string $id
     * @depreacted use FormRquest instead
     * @return array
     */
    protected function rulesForUpdate($id): array
    {
        return [];
    }
}
