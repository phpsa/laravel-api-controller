<?php

namespace Phpsa\LaravelApiController\Tests\Factories;

use Illuminate\Support\Str;
use Phpsa\LaravelApiController\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Phpsa\LaravelApiController\Tests\Models\Project;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name'        => $this->faker->name(),
            'description' => $this->faker->sentence(),
        ];
    }
}
