<?php

namespace Phpsa\LaravelApiController\Tests;

use Mockery;
use Illuminate\Support\Facades\App;
use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Http\Resources\ApiResource;

use Phpsa\LaravelApiController\Http\Resources\ApiCollection;
use Phpsa\LaravelApiController\Tests\Resources\UserResource;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;
use Phpsa\LaravelApiController\Tests\Controllers\ProjectController;
use Phpsa\LaravelApiController\Tests\Resources\UserResourceCollection;

class HasResourcesTest extends TestCase
{


    public function test_resources_logic()
    {

        $class = App::make(UserController::class);
        $this->assertInstanceOf(UserController::class, $class);

        $this->assertEquals(UserResource::class, $class->getResourceSingle());
        $this->assertEquals(UserResourceCollection::class, $class->getResourceCollection());

        $pclass = App::make(ProjectController::class);
        $this->assertInstanceOf(ProjectController::class, $pclass);

        $this->assertEquals(ApiResource::class, $pclass->getResourceSingle());
        $this->assertEquals(ApiCollection::class, $pclass->getResourceCollection());

        $getDefaultFields = self::getMethod($class, 'getDefaultFields');
        $this->assertEquals([
            'name','email'
        ], $getDefaultFields->invoke($class));

        $pgetDefaultFields = self::getMethod($pclass, 'getDefaultFields');
        $this->assertEquals([
            '*'
        ], $pgetDefaultFields->invoke($pclass));

        $getAllowedScopes = self::getMethod($class, 'getAllowedScopes');
        $this->assertEquals([
            'Has2Fa'
        ], $getAllowedScopes->invoke($class));

        $pgetAllowedScopes = self::getMethod($pclass, 'getAllowedScopes');
        $getAllowedScopes = self::getMethod($class, 'getAllowedScopes');
        $this->assertEquals([], $pgetAllowedScopes->invoke($pclass));
    }

    public function test_scope_from_request()
    {
        $class = Mockery::mock(UserController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        app()->instance(UserController::class, $class);
        $class->shouldReceive('parseScopeValue')->once()->andReturn(true);
        $class->__construct();

       // $class = App::make($class);
        $request = $this->createRequest('GET', '/?has2Fa=1&notCallable=0');

        $req = self::getMethod($class, 'validateRequestType');
        $req->invokeArgs($class, [$request]);
        $parseAllowedScopes = self::getMethod($class, 'parseAllowedScopes');
        $parseAllowedScopes->invoke($class);
    }

    public function test_block_scope_from_request()
    {
        $class = Mockery::mock(ProjectController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        app()->instance(ProjectController::class, $class);
        $class->shouldReceive('parseScopeValue')->never();
        $class->__construct();

        $this->assertInstanceOf(ProjectController::class, $class);

        //$class = App::make($class);
        $request = $this->createRequest('GET', '/?has2Fa=1&notCallable=0');
        $req = self::getMethod($class, 'validateRequestType');
        $req->invokeArgs($class, [$request]);

        $parseAllowedScopes = self::getMethod($class, 'parseAllowedScopes');
        $parseAllowedScopes->invoke($class);
    }
}
