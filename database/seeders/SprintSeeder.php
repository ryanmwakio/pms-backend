<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Seeder;

class SprintSeeder extends Seeder
{
    public function run(): void
    {
        $pms = Project::where('key', 'PMS')->first();

        $sprints = [
            [
                'name'         => 'Sprint 1',
                'goal'         => 'Ship auth & onboarding',
                'status'       => 'completed',
                'start_date'   => '2026-06-01',
                'end_date'     => '2026-06-14',
                'started_at'   => '2026-06-01 09:00:00',
                'completed_at' => '2026-06-14 18:00:00',
                'velocity'     => 13,
            ],
            [
                'name'       => 'Sprint 2',
                'goal'       => 'Dashboard, API hardening, mobile',
                'status'     => 'active',
                'start_date' => '2026-06-28',
                'end_date'   => '2026-07-11',
                'started_at' => '2026-06-28 09:00:00',
                'velocity'   => null,
            ],
            [
                'name'       => 'Sprint 3',
                'goal'       => 'Integrations & reporting',
                'status'     => 'planned',
                'start_date' => '2026-07-12',
                'end_date'   => '2026-07-25',
                'velocity'   => null,
            ],
        ];

        foreach ($sprints as $sprintData) {
            Sprint::firstOrCreate(
                ['project_id' => $pms->id, 'name' => $sprintData['name']],
                array_merge($sprintData, ['project_id' => $pms->id])
            );
        }
    }
}
