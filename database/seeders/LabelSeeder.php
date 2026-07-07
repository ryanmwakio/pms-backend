<?php

namespace Database\Seeders;

use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'acme')->first();

        $labels = [
            ['name' => 'bug',         'color' => '#ef4444'],
            ['name' => 'feature',     'color' => '#4264f5'],
            ['name' => 'improvement', 'color' => '#10b981'],
            ['name' => 'tech-debt',   'color' => '#f59e0b'],
            ['name' => 'design',      'color' => '#ec4899'],
            ['name' => 'security',    'color' => '#ef4444'],
            ['name' => 'performance', 'color' => '#8b5cf6'],
            ['name' => 'docs',        'color' => '#6b7280'],
        ];

        foreach ($labels as $label) {
            Label::firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $label['name']],
                ['color' => $label['color']]
            );
        }
    }
}
