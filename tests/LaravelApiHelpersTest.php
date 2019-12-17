<?php

namespace Phpsa\LaravelApiController\Tests;

use Orchestra\Testbench\TestCase;
use Phpsa\LaravelApiController\Facades\LaravelApiController;
use Phpsa\LaravelApiController\Helpers;
use Phpsa\LaravelApiController\ServiceProvider;

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
            'testTwo' => 'test_two',
        ];

        $transposed = Helpers::snakeCaseArrayKeys($array);

        $this->assertSame(array_keys($transposed), array_values($array));
    }

    public function testCamelCasing()
    {
        $array = [
            'test_one' => 'testOne',
            'testTwo' => 'testTwo',
        ];

        $transposed = Helpers::camelCaseArrayKeys($array);

        $this->assertSame(array_keys($transposed), array_values($array));
    }

    public function testArrayExcludes()
    {
        $allowedFields = [
            'field1',
            'field2',
            'field3',
            'field4',
            'field5',
        ];

        $excludeFields = [
            'field2',
        ];

        $inputData = [
            'field1',
            'field2',
            'field3',
            'field4',
            'field5',
            'field6',
        ];

        $remaining = Helpers::excludeArrayValues($inputData, $excludeFields, $allowedFields);

        $this->assertSame([
            'field1',
            'field3',
            'field4',
            'field5',
        ], array_values($remaining));

        $remaining = Helpers::excludeArrayValues($inputData, [], $allowedFields);

        $this->assertSame([
            'field1',
            'field2',
            'field3',
            'field4',
            'field5',
        ], array_values($remaining));
    }
}
