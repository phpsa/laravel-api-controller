<?php

namespace Phpsa\LaravelApiController\Tests\Relationships;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Models\UserProfile;
use Phpsa\LaravelApiController\Tests\Controllers\UserProfileController;

class BelongsToTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        User::factory(4)->create(['referrer' => 'abc']);
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
        $router->apiResource('userprofiles', UserProfileController::class);
    }

    public function test_get_records_with_belongs_to_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('userprofiles?include=id,user_id,user,user.id');

        $response->assertOk();
        $response->assertJsonPath('data.*.user_id', [2, 3]);
        $response->assertJsonPath('data.*.user.id', [2, 3]);
        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_record_with_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('userprofiles/1?include=user');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.user_id', 2);
        $response->assertJsonPath('data.user.id', 2);
        $response->assertJsonPath('data.phone', '23456789');
        $response->assertJsonPath('data.user.name', User::find(2)->name);
    }

    public function test_update_record_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('userprofiles/1', [
            'user' => ['name' => 'NewName'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.name', 'NewName');
        $this->assertEquals(User::find(2)->name, 'NewName');
    }

    public function test_delete_field_on_related_record_using_null_via_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('userprofiles/1', [
            'user' => ['referrer' => null],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.referrer', null);
        $this->assertNull(User::find(2)->referrer);
    }

    public function test_create_record_and_related_with_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->postJson('userprofiles?include=user', [
            'phone'   => '56789012',
            'address' => '567 Fake St',
            'user'    => [
                'name'     => 'Jane Doe',
                'email'    => 'jane@doe.com',
                'password' => 'secret',
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Jane Doe');
        $response->assertJsonPath('data.profile.phone', '34567890');
        $user = User::find(5);
        $this->assertEquals($user->name, 'Jane Doe');
        $this->assertEquals($user->profile->phone, '56789012');
    }
}
