<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Status;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $workspace   = Workspace::where('slug', 'acme')->first();
        $platformTeam = Team::where('name', 'Platform')->first();
        $productTeam  = Team::where('name', 'Product')->first();
        $alex         = User::where('email', 'alex@acme.io')->first();
        $sam          = User::where('email', 'sam@acme.io')->first();
        $jordan       = User::where('email', 'jordan@acme.io')->first();

        $projects = [
            [
                'workspace_id' => $workspace->id,
                'team_id'      => $platformTeam->id,
                'lead_id'      => $alex->id,
                'name'         => 'Project Management System',
                'key'          => 'PMS',
                'description'  => 'Core PMS platform — authentication, dashboard, API, and mobile.',
                'color'        => '#4264f5',
                'health'       => 'on-track',
                'progress'     => 62,
                'start_date'   => '2026-05-01',
                'target_date'  => '2026-08-31',
            ],
            [
                'workspace_id' => $workspace->id,
                'team_id'      => $productTeam->id,
                'lead_id'      => $sam->id,
                'name'         => 'Integrations Hub',
                'key'          => 'INT',
                'description'  => 'Slack, GitHub, Jira, and third-party integrations.',
                'color'        => '#10b981',
                'health'       => 'at-risk',
                'progress'     => 28,
                'start_date'   => '2026-06-15',
                'target_date'  => '2026-09-30',
            ],
            [
                'workspace_id' => $workspace->id,
                'team_id'      => $platformTeam->id,
                'lead_id'      => $jordan->id,
                'name'         => 'Mobile App',
                'key'          => 'MOB',
                'description'  => 'React Native mobile companion app.',
                'color'        => '#8b5cf6',
                'health'       => 'off-track',
                'progress'     => 12,
                'start_date'   => '2026-07-01',
                'target_date'  => '2026-11-30',
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::firstOrCreate(
                ['workspace_id' => $workspace->id, 'key' => $projectData['key']],
                $projectData
            );

            // Create default statuses only if none exist
            if ($project->statuses()->count() === 0) {
                $statuses = [
                    ['name' => 'To Do',       'color' => '#6b7280', 'icon' => '○', 'category' => 'todo',        'position' => 0, 'is_default' => true],
                    ['name' => 'In Progress', 'color' => '#4264f5', 'icon' => '◑', 'category' => 'in_progress',  'position' => 1],
                    ['name' => 'In Review',   'color' => '#f59e0b', 'icon' => '◕', 'category' => 'in_progress',  'position' => 2],
                    ['name' => 'Done',        'color' => '#10b981', 'icon' => '●', 'category' => 'done',          'position' => 3],
                    ['name' => 'Blocked',     'color' => '#ef4444', 'icon' => '⊗', 'category' => 'in_progress',  'position' => 4],
                ];
                foreach ($statuses as $s) {
                    $project->statuses()->create($s);
                }
            }

            // Add all workspace members as project members
            foreach ($workspace->members as $user) {
                $role = $user->id === $project->lead_id ? 'owner' : 'member';
                $project->members()->syncWithoutDetaching([
                    $user->id => ['role' => $role],
                ]);
            }
        }
    }
}
