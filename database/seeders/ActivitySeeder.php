<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $pms    = Project::where('key', 'PMS')->first();
        $jordan = User::where('email', 'jordan@acme.io')->first();
        $morgan = User::where('email', 'morgan@acme.io')->first();
        $alex   = User::where('email', 'alex@acme.io')->first();
        $sam    = User::where('email', 'sam@acme.io')->first();
        $riley  = User::where('email', 'riley@acme.io')->first();
        $casey  = User::where('email', 'casey@acme.io')->first();

        $pms3  = Issue::where('key', 'PMS-3')->first();
        $pms4  = Issue::where('key', 'PMS-4')->first();
        $pms5  = Issue::where('key', 'PMS-5')->first();
        $pms7  = Issue::where('key', 'PMS-7')->first();
        $pms10 = Issue::where('key', 'PMS-10')->first();
        $pms2  = Issue::where('key', 'PMS-2')->first();

        $events = [
            [$jordan, $pms3,  $pms, 'status_changed', ['from' => 'To Do', 'to' => 'In Progress'],       '2026-07-06 09:00:00'],
            [$morgan, $pms4,  $pms, 'commented',       [],                                               '2026-07-06 10:30:00'],
            [$alex,   $pms10, $pms, 'created',         ['key' => 'PMS-10', 'title' => 'Keyboard shortcut help modal'], '2026-07-06 12:00:00'],
            [$sam,    $pms2,  $pms, 'status_changed',  ['from' => 'In Review', 'to' => 'Done'],          '2026-07-05 17:00:00'],
            [$riley,  $pms5,  $pms, 'status_changed',  ['from' => 'In Progress', 'to' => 'In Review'],   '2026-07-05 14:00:00'],
            [$casey,  $pms7,  $pms, 'commented',       [],                                               '2026-07-05 11:00:00'],
        ];

        foreach ($events as [$user, $issue, $project, $action, $meta, $createdAt]) {
            if (! $issue || ! $user) {
                continue;
            }

            Activity::firstOrCreate(
                [
                    'user_id'    => $user->id,
                    'issue_id'   => $issue->id,
                    'action'     => $action,
                    'created_at' => $createdAt,
                ],
                [
                    'project_id' => $project->id,
                    'meta'       => $meta ?: null,
                ]
            );
        }
    }
}
