<?php

namespace Phpsa\LaravelApiController\Tests\Relationships;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Tests\Models\Task;
use Phpsa\LaravelApiController\Tests\Models\User;
use Phpsa\LaravelApiController\Tests\Models\Project;
use Phpsa\LaravelApiController\Tests\Controllers\ProjectController;

class HasManyTest extends TestCase
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
        Task::create([
            'project_id' => 1,
            'name'       => 'P1.TaskOne',
        ]);
        Task::create([
            'project_id' => 1,
            'name'       => 'P1.TaskTwoOther',
        ]);
        Task::create([
            'project_id' => 2,
            'name'       => 'P2.TaskOne',
        ]);
        Task::create([
            'project_id' => 3,
            'name'       => 'P3.TaskOther',
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
        $router->apiResource('projects', ProjectController::class);
    }

    public function test_get_records_with_has_many_relationship()
    {
        $response = $this->getJson('projects?include=tasks');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.0.tasks');
        $response->assertJsonCount(1, 'data.1.tasks');
        $response->assertJsonPath('data.0.tasks.*.id', [1,2]);
        $response->assertJsonPath('data.1.tasks.*.id', [3]);
        $response->assertJsonPath('meta.total', 4);
    }

    public function test_filter_records_by_related_data_with_has_many_relationship()
    {
        $response = $this->getJson('projects?include=tasks&filter[tasks.name~]=other');

        $response->assertOk();
        $response->assertJsonPath('data.*.id', [1,3]);
        $response->assertJsonPath('data.*.tasks.*.id', [2,4]);
        $response->assertJsonPath('meta.total', 2);
    }

    public function test_get_record_with_has_many_relationship()
    {
        $response = $this->getJson('projects/1?include=tasks');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonCount(2, 'data.tasks');
        $response->assertJsonPath('data.tasks.*.id', [1,2]);
    }

    public function test_update_record_has_many_relationship()
    {
        $response = $this->putJson('projects/1', [
            'tasks' => [
                [
                    'id'   => 1,
                    'name' => 'NewName'
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'data.tasks');
        $response->assertJsonPath('data.tasks.0.id', 1);
        $response->assertJsonPath('data.tasks.0.name', 'NewName');
        $response->assertJsonPath('data.tasks.1.name', 'P1.TaskTwoOther');
        $this->assertEquals(Task::find(1)->name, 'NewName');
    }

    public function test_delete_related_records_using_null_on_has_many_relationship_with_sync()
    {
        $response = $this->putJson('projects/1?sync[tasks]=true', [
            'name'  => 'NewName',
            'tasks' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(0, 'data.tasks');
        $this->assertCount(0, Project::find(1)->tasks);
    }

    public function test_delete_related_records_using_array_on_has_many_relationship_with_sync()
    {
        $response = $this->putJson('projects/1?sync[tasks]=true', [
            'name'  => 'NewName',
            'tasks' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(0, 'data.tasks');
        $this->assertCount(0, Project::find(1)->tasks);
    }

    public function test_ignore_related_records_where_relation_field_is_null_on_has_many_relationship_without_sync()
    {
        $response = $this->putJson('projects/1', [
            'name'  => 'NewName',
            'tasks' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(2, 'data.tasks');
        $this->assertCount(2, Project::find(1)->tasks);
    }

    public function test_ignore_related_records_where_relation_field_is_empty_array_on_has_many_relationship_without_sync()
    {
        $response = $this->putJson('projects/1', [
            'name'  => 'NewName',
            'tasks' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'NewName');
        $response->assertJsonCount(2, 'data.tasks');
        $this->assertCount(2, Project::find(1)->tasks);
    }

    public function test_delete_one_related_record_on_has_many_relationship()
    {
        $this->markTestSkipped('Not implemented yet, unsure of request format');
        $response = $this->putJson('projects/1', [
            'tasks' => [
                1 => null,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.profile.address', null);
        $this->assertNull(User::find(2)->profile->address);
    }

    public function test_create_record_via_has_one_relationship()
    {
        $response = $this->putJson('projects/1', [
            'tasks' => [
                [
                    'name'   => 'NewTask',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.tasks.2.name', 'NewTask');
        $response->assertJsonCount(3, 'data.tasks');
        $this->assertCount(3, Project::find(1)->tasks);
    }

    public function test_create_record_and_related_with_has_many_relationship()
    {
        $response = $this->postJson('projects', [
            'name'        => 'NewProject',
            'description' => 'NewProject description',
            'tasks'       => [
                [
                    'name' => 'NewTask1',
                ],
                [
                    'name' => 'NewTask2',
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'NewProject');
        $response->assertJsonCount(2, 'data.tasks');
        $response->assertJsonPath('data.tasks.0.name', 'NewTask1');
        $response->assertJsonPath('data.tasks.1.name', 'NewTask2');
        $this->assertCount(2, Project::find(5)->tasks);
    }
}
