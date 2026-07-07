<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name'         => $this->faker->word().' Team',
            'color'        => $this->faker->hexColor(),
            'description'  => $this->faker->sentence(),
        ];
    }
}
