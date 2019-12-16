<?php

namespace Phpsa\LaravelApiController\Tests;

use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\UriParser;
use Phpsa\LaravelApiController\ServiceProvider;
use Phpsa\LaravelApiController\Facades\LaravelApiController;

class LaravelApiHelpersTest extends TestCase
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

    public function testSnakeCasing()
    {
        $array = [
            'test_one' => 'test_one',
            'testTwo' => 'test_two'
        ];

        $transposed = Helpers::snakeCaseArrayKeys($array);

        $this->assertSame(array_keys($transposed), array_values($array));
    }

    public function testCamelCasing()
    {
        $array = [
            'test_one' => 'testOne',
            'testTwo' => 'testTwo'
        ];

        $transposed = Helpers::camelCaseArrayKeys($array);

        $this->assertSame(array_keys($transposed), array_values($array));
    }

    public function testArrayExcludes()
    {
        $data1 = [];
        $data2 = [];
        $this->markTestSkipped('Still to build');
    }


    /**
     * camelCaseArrayKeys
snakeCaseArrayKeys
    public static function snake(string $value): string
filterFieldsFromRequest
excludeArrayValues
     */
}
