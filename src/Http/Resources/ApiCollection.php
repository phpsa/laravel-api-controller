<?php

namespace Phpsa\LaravelApiController\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiCollection extends ResourceCollection
{
    protected function collects()
    {
        return parent::collects() ?? ApiResource::class;
    }
}
