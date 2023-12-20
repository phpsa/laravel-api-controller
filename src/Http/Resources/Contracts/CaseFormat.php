<?php

namespace Phpsa\LaravelApiController\Http\Resources\Contracts;

use Illuminate\Http\Request;
use Phpsa\LaravelApiController\Helpers;

trait CaseFormat
{
    protected function caseFormat(Request $request, array $data): array
    {
        $case = $request->header('X-Accept-Case-Type');
        if (!is_string($case)) {
            return $data;
        }
        return match (str($case)->lower()) {
            'camel', 'camel-case' => Helpers::camelCaseArrayKeys($data),
            'snake', 'snake-case' => Helpers::snakeCaseArrayKeys($data),
            default => $data,
        };
    }
}
