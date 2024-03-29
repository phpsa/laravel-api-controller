<?php

namespace Phpsa\LaravelApiController\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Phpsa\LaravelApiController\Http\Resources\Contracts\AllowableFields;
use Phpsa\LaravelApiController\Http\Resources\Contracts\CaseFormat;
use Illuminate\Http\Request;

class ApiResource extends JsonResource
{
    use AllowableFields;
    use CaseFormat;

    protected ?string $fieldKey = null;

    public function setFieldKey(?string $fieldKey): static
    {
        $this->fieldKey = $fieldKey;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        $data = $this->onlyAllowed($request);
        return $this->caseFormat($request, $data);
    }
}
