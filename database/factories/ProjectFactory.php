<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'lead_id'      => User::factory(),
            'name'         => $this->faker->words(3, true),
            'key'          => strtoupper($this->faker->lexify('???')),
            'color'        => $this->faker->hexColor(),
            'health'       => 'on-track',
            'progress'     => 0,
        ];
    }
}
