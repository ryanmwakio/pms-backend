<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'status_id'   => Status::factory(),
            'reporter_id' => User::factory(),
            'key'         => strtoupper($this->faker->lexify('???')).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'title'       => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'type'        => $this->faker->randomElement(['task', 'story', 'bug']),
            'priority'    => $this->faker->randomElement(['urgent', 'high', 'medium', 'low', 'none']),
            'story_points'=> $this->faker->randomElement([1, 2, 3, 5, 8, 13]),
            'position'    => $this->faker->numberBetween(0, 100),
        ];
    }
}
