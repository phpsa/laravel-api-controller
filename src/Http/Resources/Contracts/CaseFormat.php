<?php

namespace Phpsa\LaravelApiController\Http\Resources\Contracts;

use Phpsa\LaravelApiController\Helpers;

trait CaseFormat
{
    protected function caseFormat($request, $data)
    {
        switch (strtolower($request->header('X-Accept-Case-Type'))) {
            case 'camel':
            case 'camel-case':
                return Helpers::camelCaseArrayKeys($data);

            case 'snake':
            case 'snake-case':
                return Helpers::snakeCaseArrayKeys($data);
        }

        return $data;
    }
}
