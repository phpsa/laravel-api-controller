<?php

namespace Phpsa\LaravelApiController\Http\Resources;

use Phpsa\LaravelApiController\Helpers;
use Illuminate\Http\Resources\Json\JsonResource;
use Phpsa\LaravelApiController\Http\Resources\Contracts\CaseFormat;
use Phpsa\LaravelApiController\Http\Resources\Contracts\AllowableFields;

class ApiResource extends JsonResource
{
    use AllowableFields;
    use CaseFormat;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = $this->onlyAllowed($request);

        return $this->caseFormat($request, $data);
    }
}
