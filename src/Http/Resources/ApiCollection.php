<?php

namespace Phpsa\LaravelApiController\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Route;

class ApiCollection extends ResourceCollection
{

    protected ?string $fieldKey = null;

    protected function collects()
    {
        return parent::collects() ?? Route::current()->controller->getResourceSingle();
    }

    public function setFieldKey(?string $fieldKey): static
    {
        $this->fieldKey = $fieldKey;
        return $this;
    }

     /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->collection->map(
            fn($item) => $item->setFieldKey($this->fieldKey)->toArray($request)
        )->all();
    }
}
