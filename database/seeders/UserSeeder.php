<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'            => 'Alex Rivera',
                'email'           => 'alex@acme.io',
                'password'        => Hash::make('password'),
                'avatar_color'    => '#4264f5',
                'avatar_initials' => 'AR',
                'role'            => 'Engineering Lead',
            ],
            [
                'name'            => 'Jordan Kim',
                'email'           => 'jordan@acme.io',
                'password'        => Hash::make('password'),
                'avatar_color'    => '#10b981',
                'avatar_initials' => 'JK',
                'role'            => 'Senior Engineer',
            ],
            [
                'name'            => 'Sam Chen',
                'email'           => 'sam@acme.io',
                'password'        => Hash::make('password'),
                'avatar_color'    => '#f59e0b',
                'avatar_initials' => 'SC',
                'role'            => 'Product Manager',
            ],
            [
                'name'            => 'Morgan Lee',
                'email'           => 'morgan@acme.io',
                'password'        => Hash::make('password'),
                'avatar_color'    => '#ef4444',
                'avatar_initials' => 'ML',
                'role'            => 'Designer',
            ],
            [
                'name'            => 'Casey Park',
                'email'           => 'casey@acme.io',
                'password'        => Hash::make('password'),
                'avatar_color'    => '#8b5cf6',
                'avatar_initials' => 'CP',
                'role'            => 'QA Engineer',
            ],
            [
                'name'            => 'Riley Wong',
                'email'           => 'riley@acme.io',
                'password'        => Hash::make('password'),
                'avatar_color'    => '#ec4899',
                'avatar_initials' => 'RW',
                'role'            => 'Backend Engineer',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(['email' => $userData['email']], $userData);
        }
    }
}
