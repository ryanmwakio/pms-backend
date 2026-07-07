<?php

use App\Models\Issue;
use App\Models\Sprint;

describe('Reports', function () {

    it('returns the overview report', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        Issue::factory()->count(5)->create([
            'project_id'  => $project->id,
            'status_id'   => $status->id,
            'reporter_id' => $user->id,
        ]);

        $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/reports/overview")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'issues_created', 'issues_closed', 'completion_rate',
                    'avg_cycle_time', 'avg_lead_time', 'total_open', 'by_type', 'by_priority',
                ],
            ]);
    });

    it('returns velocity data', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        Sprint::factory()->count(3)->create([
            'project_id' => $project->id,
            'status'     => 'completed',
            'velocity'   => 20,
        ]);

        $res = $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/reports/velocity")
            ->assertOk();

        expect($res->json('data'))->toHaveCount(3);
    });

    it('returns burndown data for active sprint', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $sprint = Sprint::factory()->create([
            'project_id' => $project->id,
            'status'     => 'active',
            'start_date' => now()->subDays(5),
            'end_date'   => now()->addDays(9),
            'started_at' => now()->subDays(5),
        ]);

        Issue::factory()->create([
            'project_id'   => $project->id,
            'sprint_id'    => $sprint->id,
            'status_id'    => $status->id,
            'reporter_id'  => $user->id,
            'story_points' => 10,
        ]);

        $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/reports/burndown")
            ->assertOk()
            ->assertJsonStructure(['data' => ['sprint', 'total_points', 'data']]);
    });

    it('returns team performance report', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/reports/team-performance")
            ->assertOk()
            ->assertJsonStructure(['data' => [['user', 'closed_count', 'closed_points', 'active_count']]]);
    });
});
