<?php

namespace Phpsa\LaravelApiController\Tests\Relationships;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\Task;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Models\Project;
use Phpsa\LaravelApiController\Tests\Controllers\ProjectController;

class ManyToManyPolymorphicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        for ($i = 1; $i <= 4; $i++) {
            Project::create([
                'name'          => "Project{$i}",
                'description'   => "Project{$i} description",
                'cost_per_hour' => $i * 100,
            ]);
        }
        $project1 = Project::find(1);
        $user1 = User::factory()->createOne();
        $user2 = User::factory()->createOne();
        $user3 = User::factory()->createOne();
        $project1->users()->attach($user1);
        $project1->users()->attach($user2, ['team_leader' => true]);
        $project1->users()->attach($user3);
        $project2 = Project::find(2);
        $project2->users()->attach($user3, ['team_leader' => true]);
        $project2->users()->attach($user2);
        $project3 = Project::find(3);
        $project3->users()->attach($user3, ['team_leader' => true]);
        $project3->users()->attach($user3);
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
        $router->apiResource('projects', ProjectController::class);
    }

    public function test_get_records_with_many_to_many_polymorphic_relationship_with_pivot()
    {
        $response = $this->getJson('projects?include=users');

        $response->assertOk();
        $response->assertJsonCount(3, 'data.0.users');
        $response->assertJsonPath('data.0.users.*.id', [1,2,3]);
        $response->assertJsonPath('data.0.users.0.pivot.team_leader', 0);
        $response->assertJsonPath('data.0.users.1.pivot.team_leader', 1);
        $response->assertJsonPath('data.0.users.2.pivot.team_leader', 0);
        $response->assertJsonPath('data.1.users.0.pivot.team_leader', 1);
        $response->assertJsonPath('data.1.users.1.pivot.team_leader', 0);
        $response->assertJsonPath('meta.total', 4);
    }

    public function test_filter_records_by_related_data_with_many_to_many_polymorphic_relationship_with_pivot()
    {
        $response = $this->getJson('projects?include=users&filter[users.id]=2');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 2);
    }

    public function test_filter_records_by_related_pivot_data_with_many_to_many_polymorphic_relationship_with_pivot()
    {
        //@TODO: Should only return the one result where User 2 is the team leader
        $response = $this->getJson('projects?include=users&filter[users.id]=2&filter[pivot_team_leader]=1');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', 1);
    }

    public function test_get_record_with_many_to_many_polymorphic_relationship_with_pivot()
    {
        $response = $this->getJson('projects/1?include=users');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonCount(3, 'data.users');
        $response->assertJsonPath('data.users.*.id', [1,2,3]);
    }

    public function test_update_record_on_many_to_many_polymorphic_relationship_with_pivot()
    {
        $response = $this->putJson('projects/1', [
            'users' => [
                [
                    'id'   => 1,
                    'name' => 'NewUserName',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.users.0.id', 1);
        $response->assertJsonPath('data.users.0.name', 'NewUserName');
        $this->assertEquals(User::find(1)->name, 'NewUserName');
    }

    public function test_update_pivot_field_on_many_to_many_polymorphic_relationship_with_pivot()
    {
        //@TODO: Unsure of format fpr updating pivot fields
        $response = $this->putJson('projects/1', [
            'users' => [
                [
                    'id'                => 1,
                    'pivot.team_leader' => 1,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.users.0.id', 1);
        $response->assertJsonPath('data.users.0.pivot.team_leader', 1);
        $this->assertEquals(Project::find(1)->users()->first()->pivot->team_leader, 1);
    }

    public function test_detach_related_records_using_null_on_many_to_many_polymorphic_relationship_with_pivot_with_sync()
    {
        $response = $this->putJson('projects/1?sync[users]=true', [
            'name'  => 'NewName',
            'users' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(0, 'data.users');
        $this->assertCount(0, Project::find(1)->users);
    }

    public function test_detach_related_records_using_array_on_many_to_many_polymorphic_relationship_with_pivot_with_sync()
    {
        $response = $this->putJson('projects/1?sync[users]=true', [
            'name'  => 'NewName',
            'users' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(0, 'data.users');
        $this->assertCount(0, Project::find(1)->users);
    }

    public function test_ignore_related_records_where_relation_field_is_null_on_many_to_many_polymorphic_relationship_with_pivot_without_sync()
    {
        $response = $this->putJson('projects/1', [
            'name'  => 'NewName',
            'users' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(2, 'data.users');
        $this->assertCount(2, Project::find(1)->users);
    }

    public function test_ignore_related_records_where_relation_field_is_empty_array_on_many_to_many_polymorphic_relationship_with_pivot_without_sync()
    {
        $response = $this->putJson('projects/1', [
            'name'  => 'NewName',
            'users' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(2, 'data.users');
        $this->assertCount(2, Project::find(1)->users);
    }

    public function test_detach_one_related_record_on_many_to_many_polymorphic_relationship_with_pivot()
    {
        $response = $this->putJson('projects/1?include=users', [
            'users' => [
                1 => null,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.profile.address', null);
        $this->assertNull(User::find(2)->profile->address);
    }

    public function test_detach_one_related_record_using_pivot_field_on_many_to_many_polymorphic_relationship_with_pivot()
    {
        //@TODO: Unsure of request format. Or do we have to use the sync method?
        $this->markTestSkipped('Test not implemented yet');

        // Project 3 has User 3 twice, once as a team leader and once as normal.
        // Make request to remove one of these
    }

    public function test_attach_record_via_many_to_many_polymorphic_relationship_with_pivot()
    {
        $this->markTestSkipped('Test not implemented yet');
    }

    public function test_create_record_and_attach_related_with_many_to_many_polymorphic_relationship_with_pivot()
    {
        $this->markTestSkipped('Test not implemented yet');
    }

    public function test_create_both_records_and_attach_related_with_many_to_many_polymorphic_relationship_with_pivot()
    {
        $this->markTestSkipped('Test not implemented yet');
    }
}
