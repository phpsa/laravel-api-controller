<?php

namespace Phpsa\LaravelApiController\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelApiController extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-api-controller';
    }
}
