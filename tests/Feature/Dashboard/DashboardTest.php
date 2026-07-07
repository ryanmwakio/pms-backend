<?php

use App\Models\Issue;
use App\Models\Sprint;

describe('Dashboard', function () {

    it('returns the dashboard data for a project', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        // Create an active sprint
        $sprint = Sprint::factory()->create([
            'project_id' => $project->id,
            'status'     => 'active',
            'started_at' => now(),
        ]);

        // Add some issues
        Issue::factory()->count(4)->create([
            'project_id'  => $project->id,
            'status_id'   => $status->id,
            'sprint_id'   => $sprint->id,
            'reporter_id' => $user->id,
        ]);

        $res = $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/dashboard")
            ->assertOk();

        expect($res->json('data'))->toHaveKeys([
            'project',
            'sprint_progress',
            'status_breakdown',
            'priority_breakdown',
            'team_workload',
            'recent_activity',
            'upcoming_deadlines',
            'milestones',
        ]);

        expect($res->json('data.sprint_progress.total'))->toBe(4);
    });

    it('returns zero sprint progress when no active sprint', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $res = $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/dashboard")
            ->assertOk();

        expect($res->json('data.sprint_progress.pct'))->toBe(0);
    });
});
