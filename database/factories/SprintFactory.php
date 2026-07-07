<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class SprintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name'       => 'Sprint '.$this->faker->unique()->numberBetween(1, 99),
            'goal'       => $this->faker->sentence(),
            'status'     => 'planned',
            'start_date' => now()->addDays(1),
            'end_date'   => now()->addDays(14),
        ];
    }
}
