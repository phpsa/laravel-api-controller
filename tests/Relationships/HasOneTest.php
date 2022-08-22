<?php

namespace Phpsa\LaravelApiController\Tests\Relationships;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Models\UserProfile;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;

class HasOneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        User::factory(4)->create();
        UserProfile::create([
            'user_id' => 2,
            'phone'   => '23456789',
            'address' => '23 Fake St',
        ]);
        UserProfile::create([
            'user_id' => 3,
            'phone'   => '34567890',
            'address' => '34 Fake St',
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

    public function test_get_records_with_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('users?include=profile,id,profile.user_id');

        $response->assertOk();
        $response->assertJsonPath('data.1.profile.user_id', 2);
        $response->assertJsonPath('data.0.profile', null);
    }

    public function test_filter_records_by_related_data_with_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('users?include=profile,id&filter[profile.phone]=23456789');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_get_record_with_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('users/2?include=profile,id,profile.user_id');

        $response->assertOk();
        $response->assertJsonPath('data.id', 2);
        $response->assertJsonPath('data.profile.user_id', 2);
        $response->assertJsonPath('data.profile.phone', '23456789');
    }

    public function test_update_record_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/2', [
            'profile' => ['phone' => '22222222'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.profile.phone', '22222222');
        $this->assertEquals(User::find(2)->profile->phone, '22222222');
    }

    public function test_delete_field_on_related_record_using_null_via_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/2', [
            'profile' => ['address' => null],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.profile.address', null);
        $this->assertNull(User::find(2)->profile->address);
    }

    public function test_delete_related_record_using_null_via_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/2?include=profile,id', [
            'profile' => null,
        ]);

        $response->assertJsonPath('data.profile', null);
        $this->assertEquals(User::find(2)->profile, null);
    }

    public function test_create_record_via_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/1?include=profile,id', [
            'profile' => [
                'phone'   => '12345678',
                'address' => '12 Fake St',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.profile.phone', '12345678');
        $this->assertEquals(User::first()->profile->address, '12 Fake St');
    }

    public function test_create_record_and_related_via_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->postJson('users?include=id', [
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
            'profile'  => [
                'phone'   => '567890123',
                'address' => '56 Fake St',
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Jane Doe');
        $response->assertJsonPath('data.profile.phone', '567890123');
        $user = User::find(5);
        $this->assertEquals($user->name, 'Jane Doe');
    }
}
