<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'issue_id'  => Issue::factory(),
            'user_id'   => User::factory(),
            'body'      => $this->faker->paragraph(),
            'is_edited' => false,
        ];
    }
}
