<?php

namespace Phpsa\LaravelApiController\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Route;

class ApiCollection extends ResourceCollection
{
    protected function collects()
    {
        return parent::collects() ?? Route::current()->controller->getResourceSingle();
    }
}
