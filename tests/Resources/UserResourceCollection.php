<?php

namespace Phpsa\LaravelApiController\Tests\Resources;

use Phpsa\LaravelApiController\Http\Resources\ApiCollection;

class UserResourceCollection extends ApiCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
