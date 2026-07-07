<?php

use App\Models\Issue;
use App\Models\Sprint;

describe('Sprints', function () {

    it('lists sprints for a project', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        Sprint::factory()->count(3)->create(['project_id' => $project->id]);

        $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/sprints")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('creates a sprint', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $this->withToken($token)
            ->postJson("/api/projects/{$project->id}/sprints", [
                'name'       => 'Sprint 1',
                'goal'       => 'Ship the MVP',
                'start_date' => '2026-08-01',
                'end_date'   => '2026-08-14',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Sprint 1')
            ->assertJsonPath('data.status', 'planned');
    });

    it('starts a sprint', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $sprint  = Sprint::factory()->create(['project_id' => $project->id, 'status' => 'planned']);

        $this->withToken($token)
            ->postJson("/api/sprints/{$sprint->id}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('cannot start an already active sprint', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $sprint  = Sprint::factory()->create(['project_id' => $project->id, 'status' => 'active', 'started_at' => now()]);

        $this->withToken($token)
            ->postJson("/api/sprints/{$sprint->id}/start")
            ->assertStatus(422);
    });

    it('completes a sprint and sets velocity', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project    = createProject($workspace, $user);
        $doneStatus = $project->statuses()->where('name', 'Done')->first();
        $sprint     = Sprint::factory()->create(['project_id' => $project->id, 'status' => 'active', 'started_at' => now()]);

        Issue::factory()->create([
            'project_id'  => $project->id,
            'sprint_id'   => $sprint->id,
            'status_id'   => $doneStatus->id,
            'reporter_id' => $user->id,
            'key'         => 'TST-1',
            'story_points'=> 8,
            'completed_at'=> now(),
        ]);

        $this->withToken($token)
            ->postJson("/api/sprints/{$sprint->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.velocity', 8);
    });

    it('adds and removes an issue from a sprint', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);
        $sprint  = Sprint::factory()->create(['project_id' => $project->id]);
        $issue   = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);

        $this->withToken($token)
            ->postJson("/api/sprints/{$sprint->id}/issues/{$issue->id}")
            ->assertOk();

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'sprint_id' => $sprint->id]);

        $this->withToken($token)
            ->deleteJson("/api/sprints/{$sprint->id}/issues/{$issue->id}")
            ->assertStatus(204);

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'sprint_id' => null]);
    });

    it('deletes a planned sprint and moves issues to backlog', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);
        $sprint  = Sprint::factory()->create(['project_id' => $project->id, 'status' => 'planned']);
        $issue   = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1', 'sprint_id' => $sprint->id]);

        $this->withToken($token)
            ->deleteJson("/api/sprints/{$sprint->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('sprints', ['id' => $sprint->id]);
        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'sprint_id' => null]);
    });
});
