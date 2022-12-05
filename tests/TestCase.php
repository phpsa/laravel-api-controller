<?php

namespace Phpsa\LaravelApiController\Tests;

use ReflectionClass;
use Illuminate\Http\Request;
use Orchestra\Testbench\Concerns\WithFactories;
use Phpsa\LaravelApiController\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

class TestCase extends BaseTestCase
{


    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        Factory::guessFactoryNamesUsing(function ($class) {
            return '\\Phpsa\\LaravelApiController\\Tests\Factories\\' . class_basename($class) . 'Factory';
        });
    }


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


    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('laravel-api-controller.parameters', [
            'include'      => 'include', // which hasOnes / HasMany etc to include in the response
            'filter'       => 'filter', // filter on fields
            'sort'         => 'sort', // sort the response
            'fields'       => 'fields', // fields to return
            'page'         => 'page', //Page number when pagination is on
            'group'        => 'group', // Group by query
            'addfields'    => 'addfields', //Add fields to the default fields
            'removefields' => 'removefields', //Remove fields from the default fields
            'limit'        => 'limit', // howe many records to return
        ]);
    }

    protected function createRequest(
        $method,
        $uri = '/test',
        $parameters = [],
        $content = '',
        $server = ['CONTENT_TYPE' => 'application/json'],
        $cookies = [],
        $files = []
    ) {
        $request = new Request;

        return $request->createFromBase(
            \Symfony\Component\HttpFoundation\Request::create(
                $uri,
                $method,
                $parameters,
                $cookies,
                $files,
                $server,
                $content
            )
        );
    }

    public function apiGetJson($uri, array $data = [], array $headers = [])
    {

        $headers = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'Accept'       => 'application/json',
        ], $headers);

        return $this->call(
            'GET',
            $uri,
            $data
        );
    }

    protected static function getMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    protected static function getProperty($class, $name)
    {
        $class = new ReflectionClass($class);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }
}
