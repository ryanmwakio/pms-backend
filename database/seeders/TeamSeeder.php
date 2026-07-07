<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme')->first();

        $teams = [
            [
                'name'    => 'Platform',
                'color'   => '#4264f5',
                'lead'    => 'alex@acme.io',
                'members' => ['alex@acme.io', 'jordan@acme.io', 'riley@acme.io'],
            ],
            [
                'name'    => 'Product',
                'color'   => '#10b981',
                'lead'    => 'sam@acme.io',
                'members' => ['sam@acme.io', 'morgan@acme.io'],
            ],
            [
                'name'    => 'Quality',
                'color'   => '#f59e0b',
                'lead'    => 'casey@acme.io',
                'members' => ['casey@acme.io'],
            ],
        ];

        foreach ($teams as $teamData) {
            $lead = User::where('email', $teamData['lead'])->first();

            $team = Team::firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $teamData['name']],
                [
                    'color'   => $teamData['color'],
                    'lead_id' => $lead?->id,
                ]
            );

            foreach ($teamData['members'] as $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $role = $email === $teamData['lead'] ? 'lead' : 'member';
                    $team->members()->syncWithoutDetaching([
                        $user->id => ['role' => $role],
                    ]);
                }
            }
        }
    }
}
