<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\Project;
use Illuminate\Database\Seeder;

class EpicSeeder extends Seeder
{
    public function run(): void
    {
        $pms = Project::where('key', 'PMS')->first();
        $int = Project::where('key', 'INT')->first();

        $epics = [
            ['project' => $pms, 'title' => 'Authentication & Onboarding', 'color' => '#4264f5', 'position' => 0],
            ['project' => $pms, 'title' => 'Dashboard & Analytics',       'color' => '#10b981', 'position' => 1],
            ['project' => $pms, 'title' => 'API Infrastructure',          'color' => '#f59e0b', 'position' => 2],
            ['project' => $pms, 'title' => 'Mobile Responsiveness',       'color' => '#8b5cf6', 'position' => 3],
            ['project' => $int, 'title' => 'Integrations',                'color' => '#ec4899', 'position' => 0],
        ];

        foreach ($epics as $epicData) {
            Epic::firstOrCreate(
                ['project_id' => $epicData['project']->id, 'title' => $epicData['title']],
                [
                    'color'    => $epicData['color'],
                    'position' => $epicData['position'],
                ]
            );
        }
    }
}
