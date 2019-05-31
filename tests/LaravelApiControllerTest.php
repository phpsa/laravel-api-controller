<?php

namespace Phpsa\LaravelApiController\Tests;

use Phpsa\LaravelApiController\Facades\LaravelApiController;
use Phpsa\LaravelApiController\ServiceProvider;
use Orchestra\Testbench\TestCase;

class LaravelApiControllerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'laravel-api-controller' => LaravelApiController::class,
        ];
    }

    public function testExample()
    {
        $this->assertEquals(1, 1);
    }
}
