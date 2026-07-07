<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name     = fake()->name();
        $parts    = explode(' ', trim($name));
        $initials = strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));

        return [
            'name'              => $name,
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'avatar_initials'   => $initials,
            'avatar_color'      => fake()->hexColor(),
            'role'              => fake()->jobTitle(),
            'timezone'          => 'UTC',
            'theme'             => 'system',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
