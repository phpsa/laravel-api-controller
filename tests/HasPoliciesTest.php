<?php

namespace Phpsa\LaravelApiController\Tests;

use Mockery;
use Illuminate\Support\Facades\App;
use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;
use Phpsa\LaravelApiController\Tests\Models\Policies\UserPolicy;

use function PHPUnit\Framework\assertTrue;

class HasPoliciesTest extends TestCase
{


    public function test_policy_logic()
    {
        User::factory()->create([
            'email' => 'api@laravel.dev'
        ]);
        $this->actingAs(User::first());

        $class = App::make(UserController::class);
        $this->assertInstanceOf(UserController::class, $class);

        $policy = Mockery::mock(UserPolicy::class)->makePartial();
        app()->instance(UserPolicy::class, $policy);
        $policy->shouldReceive('qualifyCollectionQueryWithUser')->once()->andReturn(null);
        $policy->shouldReceive('qualifyItemQueryWithUser')->once()->andReturn(null);
        $policy->shouldReceive('qualifyStoreDataWithUser')->withAnyArgs()->once()->andReturn([]);
        $policy->shouldReceive('qualifyUpdateDataWithUser')->withAnyArgs()->once()->andReturn([]);

        $qualifyCollectionQuery = self::getMethod($class, 'qualifyCollectionQuery');
        $qualifyItemQuery = self::getMethod($class, 'qualifyItemQuery');
        $qualifyStoreQuery = self::getMethod($class, 'qualifyStoreQuery');
        $qualifyUpdateQuery = self::getMethod($class, 'qualifyUpdateQuery');

        $qualifyCollectionQuery->invoke($class);
        $qualifyItemQuery->invoke($class);
        $qualifyStoreQuery->invokeArgs($class, [['a' => 'b']]);
        $qualifyUpdateQuery->invokeArgs($class, [['a' => 'b']]);
    }

    public function test_autorize_policy_blocks()
    {
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $class = App::make(UserController::class);

        $policy = Mockery::mock(UserPolicy::class)->makePartial();
        app()->instance(UserPolicy::class, $policy);

        $authoriseUserAction = self::getMethod($class, 'authoriseUserAction');

        $authoriseUserAction->invokeArgs($class, ['view']);
    }


    public function test_autorize_policy_allows()
    {
        User::factory()->create([
            'email' => 'api@laravel.dev'
        ]);
        $this->actingAs(User::first());

        $class = App::make(UserController::class);

        $policy = Mockery::mock(UserPolicy::class)->makePartial();
        app()->instance(UserPolicy::class, $policy);

        $authoriseUserAction = self::getMethod($class, 'authoriseUserAction');

        $res = $authoriseUserAction->invokeArgs($class, ['view', User::first()]);
        assertTrue($res);
    }
}
