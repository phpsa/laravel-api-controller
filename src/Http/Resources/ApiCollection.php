<?php

namespace Phpsa\LaravelApiController\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class ApiCollection extends ResourceCollection
{

    /**
     * Guard used to retrieve the user from the request.
     * null defaults to default guard config (auth.defaults.guard)
     *
     * @var ?string
     */
    protected ?string $guard = null;

    public function setGuard(?string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

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
    public function toArray(Request $request)
    {
        return $this->collection->map(
            fn($item) => $item->setGuard($this->guard)->setFieldKey($this->fieldKey)->toArray($request)
        )->all();
    }
}
