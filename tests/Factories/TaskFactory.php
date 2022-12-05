<?php

namespace Phpsa\LaravelApiController\Tests\Factories;

use Illuminate\Support\Str;
use Phpsa\LaravelApiController\Tests\Models\Task;
use Phpsa\LaravelApiController\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Phpsa\LaravelApiController\Tests\Models\Project;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name'       => $this->faker->name(),
            'project_id' => Project::factory(),
        ];
    }
}
