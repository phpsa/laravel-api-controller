<?php

namespace Phpsa\LaravelApiController\Tests;

use Mockery;
use function PHPUnit\Framework\assertEquals;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;
use Phpsa\LaravelApiController\Tests\Models\Policies\UserPolicy;

class UserControllerTest extends TestCase
{


    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    protected function defineRoutes($router)
    {
        $router->apiResource('users', UserController::class);
    }

    public function test_user_model()
    {
        User::factory(100)->create();
        assertEquals(100, User::count());

        $policy = Mockery::mock(UserPolicy::class)->makePartial();
        app()->instance(UserPolicy::class, $policy);
        $policy->shouldReceive('viewAny')->once()->andReturn(true);

        $this->actingAs(User::first());

        $response = $this->getJson('users');

        $response->assertStatus(200);

        $json = $response->decodeResponseJson();
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(100, $json['meta']['total']);
    }
}
