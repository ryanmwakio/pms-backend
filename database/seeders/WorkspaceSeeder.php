<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $alex = User::where('email', 'alex@acme.io')->first();

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'acme'],
            [
                'name'        => 'Acme Corp',
                'description' => 'Main company workspace',
                'color'       => '#4264f5',
                'owner_id'    => $alex->id,
            ]
        );

        // Add all users as workspace members
        $roles = [
            'alex@acme.io'   => 'owner',
            'jordan@acme.io' => 'admin',
            'sam@acme.io'    => 'admin',
            'morgan@acme.io' => 'member',
            'casey@acme.io'  => 'member',
            'riley@acme.io'  => 'member',
        ];

        foreach ($roles as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $workspace->members()->syncWithoutDetaching([
                    $user->id => ['role' => $role, 'joined_at' => now()],
                ]);
                // Set active workspace for all users
                $user->update(['active_workspace_id' => $workspace->id]);
            }
        }
    }
}
