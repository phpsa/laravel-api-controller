<?php

namespace Phpsa\LaravelApiController\Tests;

use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Event;
use function PHPUnit\Framework\assertEquals;

use Phpsa\LaravelApiController\Events\Deleted;

use Phpsa\LaravelApiController\Tests\TestCase;
use Phpsa\LaravelApiController\Events\Restored;
use Phpsa\LaravelApiController\Tests\Models\Task;
use Phpsa\LaravelApiController\Tests\Models\Project;
use Phpsa\LaravelApiController\Tests\Controllers\TaskController;
use Phpsa\LaravelApiController\Tests\Models\Policies\TaskPolicy;

class TaskControllerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $project = Project::factory()->create();
        Task::factory(100)->create(['project_id' => $project->id]);

        // $this->app->bind(TaskPolicy::class, function () {
        //     return Mockery::mock(TaskPolicy::class, function ($mock) {
        //         $mock->shouldReceive('viewAny')->andReturn(true);
        //         $mock->shouldReceive('view')->andReturn(true);
        //         $mock->shouldReceive('create')->andReturn(true);
        //         $mock->shouldReceive('update')->andReturn(true);
        //         $mock->shouldReceive('delete')->andReturn(true);
        //     });
        // });
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
        $router->apiResource('tasks', TaskController::class);
        $router->patch('tasks/restore/{task}', [TaskController::class, 'restore'])->withTrashed()->name('tasks.restore');
    }


    public function test_show_task_route()
    {
        $task = Task::first();
        $response = $this->getJson(route('tasks.show', $task->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'name',
                'completed_at'
            ],
        ]);
    }

    public function test_delete_route()
    {
        Event::fake([Deleted::class]);
        $task = Task::factory()->create();
        $response = $this->deleteJson(route('tasks.destroy', ['task' => $task->id]));
        $response->assertStatus(204);

        Event::assertDispatchedTimes(Deleted::class, 1);
    }

    public function test_restore_route()
    {
        Event::fake([Restored::class]);
        $task = Task::factory()->create();
        $task->delete();
        $response = $this->patchJson(route('tasks.restore', $task->id));
        $response->assertStatus(200);

        $task->refresh();
        $this->assertNull($task->deleted_at);
        Event::assertDispatchedTimes(Restored::class, 1);
    }

    public function test_delete_route_denied_by_policy()
    {
        $this->app->bind(TaskPolicy::class, function () {
            return Mockery::mock(TaskPolicy::class, function (MockInterface $mock) {
                $mock->makePartial();
                $mock->shouldReceive('viewAny')->andReturn(false);
                $mock->shouldReceive('view')->andReturn(false);
                $mock->shouldReceive('create')->andReturn(false);
                $mock->shouldReceive('update')->andReturn(false);
                $mock->shouldReceive('delete')->andReturn(false);
            });
        });

        Event::fake([Deleted::class]);
        $task = Task::factory()->create();
        $response = $this->deleteJson(route('tasks.destroy', $task->id));
        $response->assertStatus(403);

        Event::assertNotDispatched(Deleted::class);
    }

    public function test_restore_route_denied_by_policy()
    {
        $this->app->bind(TaskPolicy::class, function () {
            return Mockery::mock(TaskPolicy::class, function ($mock) {
                 $mock->makePartial();
                $mock->shouldReceive('viewAny')->andReturn(false);
                $mock->shouldReceive('view')->andReturn(false);
                $mock->shouldReceive('create')->andReturn(false);
                $mock->shouldReceive('update')->andReturn(false);
                $mock->shouldReceive('delete')->andReturn(false);
                $mock->shouldReceive('restore')->andReturn(false);
            });
        });

        Event::fake([Restored::class]);
        $task = Task::factory()->create();
        $task->delete();
        $response = $this->patchJson(route('tasks.restore', $task->id));
        $response->assertStatus(403);
        Event::assertNotDispatched(Restored::class);
    }



    //test limit, pagination, filter, scope.
}
