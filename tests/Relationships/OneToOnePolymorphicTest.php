<?php

namespace Phpsa\LaravelApiController\Tests\Relationships;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Controllers\UserController;

class OneToOnePolymorphicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $user1 = User::factory()->createOne()->avatar()->create([
            'url' => 'avatar1.jpg',
        ])->avatarable;
        $user2 = User::factory()->createOne()->avatar()->create([
            'url'     => 'avatar2.jpg',
            'alttext' => 'My alttext',
        ])->avatarable;
        User::factory()->createOne();
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

    public function test_get_records_with_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());
        $response = $this->getJson('users?include=avatar');

        $response->assertOk();
        $response->assertJsonPath('data.0.avatar.url', 'avatar1.jpg');
        $response->assertJsonPath('data.1.avatar.url', 'avatar2.jpg');
        $response->assertJsonPath('data.2.avatar.url', null);
    }

    public function test_filter_records_by_related_data_with_has_one_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('users?include=id,avatar&filter[avatar.url]=avatar1.jpg');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_get_record_with_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->getJson('users/1?include=id,avatar');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.avatar.url', 'avatar1.jpg');
    }

    public function test_update_record_with_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/2', [
            'avatar' => ['url' => 'avatarUpdated.jpg'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.avatar.url', 'avatarUpdated.jpg');
        $this->assertEquals(User::find(2)->avatar->url, 'avatarUpdated.jpg');
    }

    public function test_delete_field_on_related_record_using_null_via_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/2', [
            'avatar' => ['alttext' => null],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.avatar.alttext', null);
        $this->assertNull(User::find(2)->avatar->alttext);
    }

    public function test_delete_related_record_using_null_via_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/2', [
            'avatar' => null,
        ]);

        $response->assertJsonPath('data.avatar', null);
        $this->assertEquals(User::find(2)->avatar, null);
    }

    public function test_create_record_via_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->putJson('users/1', [
            'avatar' => [
                'url' => 'new-avatar.jpg',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.avatar.url', 'new-avatar.jpg');
        $this->assertEquals(User::first()->avatar->url, 'new-avatar.jpg');
    }

    public function test_create_record_and_related_via_one_to_one_polymorphic_relationship()
    {
        $this->actingAs(User::first());

        $response = $this->postJson('users', [
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
            'avatar'   => [
                'url'   => 'new-user.jpg',
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Jane Doe');
        $response->assertJsonPath('data.avatar.url', 'new-user.jpg');
        $user = User::find(5);
        $this->assertEquals($user->name, 'Jane Doe');
    }
}
