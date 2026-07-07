<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name'       => $this->faker->randomElement(['To Do', 'In Progress', 'Done']),
            'color'      => $this->faker->hexColor(),
            'icon'       => '○',
            'category'   => 'todo',
            'position'   => 0,
            'is_default' => false,
        ];
    }
}
