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

    protected function setUp(): void
    {
        parent::setUp();
        User::factory(100)->create();
        User::factory()->createOne([
            'email' => 'api@laravel.dev',
        ]);
    }

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

    public function test_user_policy_all_approved()
    {
      //  factory(User::class, 100)->create();

        assertEquals(101, User::count());

        $policy = Mockery::mock(UserPolicy::class)->makePartial();
        app()->instance(UserPolicy::class, $policy);
        $policy->shouldReceive('viewAny')->once()->andReturn(true);

        $this->actingAs(User::first());

        $response = $this->getJson('users');

        $response->assertStatus(200);

        $json = $response->decodeResponseJson();

        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(101, $json['meta']['total']);
    }

    public function test_user_policy_not_allowed()
    {
        //factory(User::class, 100)->create();

        $policy = Mockery::mock(UserPolicy::class)->makePartial();
        app()->instance(UserPolicy::class, $policy);
        $policy->shouldReceive('viewAny')->once()->andReturn(false);

        $this->actingAs(User::first());

        $response = $this->getJson('users');

        $response->assertStatus(403);
    }

    public function test_filtering()
    {
        // factory(User::class, 100)->create();
        // factory(User::class, 100)->create([
        //     'email' => 'api@laravel.dev'
        // ]);

        assertEquals(1, User::where('email', 'api@laravel.dev')->count());
        $this->actingAs(User::first());

        $url = '/users?' .  http_build_query([
            'filter' =>
            [
                'email' => 'api@laravel.dev'
            ]
        ]);

        $response = $this->getJson($url);

    //    dd($response->request);

        $response->assertStatus(200);

        $json = $response->decodeResponseJson();

        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(1, $json['meta']['total']);
        $this->assertEquals('api@laravel.dev', $json['data'][0]['email']);
    }

    public function test_calls_scopes()
    {

        //scopeHas2Fa
        // factory(User::class, 100)->create();
        // factory(User::class, 100)->create([
        //     'email' => 'api@laravel.dev'
        // ]);
        //User::factory(100)->create();
        // User::factory()->create([
        //     'email' => 'api@laravel.dev'
        // ]);

        assertEquals(1, User::where('email', 'api@laravel.dev')->count());

        User::where('id', '<=', 8)->update([
            'two_factor_secret'         => 'sdfsdf',
            'two_factor_recovery_codes' => 'sdfasdf'
        ]);

        $this->actingAs(User::first());

        $url = '/users?' .  http_build_query([
            'has2Fa' => '1'
        ]);

        $response = $this->getJson($url);

    //    dd($response->request);

        $json = $response->decodeResponseJson();

        $response->assertStatus(200);

        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(8, $json['meta']['total']);
    }


    //test limit, pagination, filter, scope.
}
