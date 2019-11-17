<?php
namespace Phpsa\LaravelApiController\Http\Resources\Contracts;

use Phpsa\LaravelApiController\Helpers;

trait CaseFormat
{

    public function toArray($request)
    {
        $data = parent::toArray($request);
        return $this->caseFormat($request, $data);
    }

    protected function caseFormat($request, $data)
    {
        switch(strtolower($request->header('X-Accept-Case-Type')))
        {
            case "camel":
            case "camel-case":
                return Helpers::camelCaseArrayKeys($data);
            break;

            case "snake":
            case "snake-case":
                    return Helpers::snakeCaseArrayKeys($data);
            break;

        }

        return $data;
    }


}

?>