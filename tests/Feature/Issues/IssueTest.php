<?php

use App\Models\Issue;
use App\Models\User;

describe('Issues', function () {

    it('lists issues for a project paginated', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        Issue::factory()->count(5)->create([
            'project_id' => $project->id,
            'status_id'  => $status->id,
            'reporter_id'=> $user->id,
        ]);

        $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/issues")
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'total', 'per_page']]);
    });

    it('returns issues for the board', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        Issue::factory()->count(3)->create([
            'project_id' => $project->id,
            'status_id'  => $status->id,
            'reporter_id'=> $user->id,
        ]);

        $res = $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/issues?board=1")
            ->assertOk();

        expect($res->json('data'))->toBeArray()->toHaveCount(3);
    });

    it('creates an issue with auto-assigned key', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $res = $this->withToken($token)
            ->postJson("/api/projects/{$project->id}/issues", [
                'title'    => 'First issue',
                'type'     => 'task',
                'priority' => 'high',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.title', 'First issue')
            ->assertJsonPath('data.key', 'TST-1');

        $this->assertDatabaseHas('issues', [
            'project_id' => $project->id,
            'key'        => 'TST-1',
        ]);
    });

    it('shows a full issue with relationships', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue = Issue::factory()->create([
            'project_id'  => $project->id,
            'status_id'   => $status->id,
            'reporter_id' => $user->id,
            'key'         => 'TST-1',
        ]);

        $this->withToken($token)
            ->getJson("/api/issues/{$issue->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'key', 'title', 'status', 'assignee', 'labels', 'comments', 'activities']]);
    });

    it('updates an issue status', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project  = createProject($workspace, $user);
        $todoStatus = defaultStatus($project);
        $doneStatus = $project->statuses()->where('name', 'Done')->first();

        $issue = Issue::factory()->create([
            'project_id'  => $project->id,
            'status_id'   => $todoStatus->id,
            'reporter_id' => $user->id,
            'key'         => 'TST-1',
        ]);

        $this->withToken($token)
            ->patchJson("/api/issues/{$issue->id}", ['status_id' => $doneStatus->id])
            ->assertOk()
            ->assertJsonPath('data.status.id', $doneStatus->id);

        // Should log an activity
        $this->assertDatabaseHas('activities', [
            'issue_id' => $issue->id,
            'action'   => 'status_changed',
        ]);
    });

    it('updates an issue assignee and creates a notification', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);
        $other   = User::factory()->create();
        $workspace->members()->attach($other->id, ['role' => 'member', 'joined_at' => now()]);

        $issue = Issue::factory()->create([
            'project_id'  => $project->id,
            'status_id'   => $status->id,
            'reporter_id' => $user->id,
            'key'         => 'TST-1',
        ]);

        $this->withToken($token)
            ->patchJson("/api/issues/{$issue->id}", ['assignee_id' => $other->id])
            ->assertOk();

        // Should notify the assignee
        $this->assertDatabaseHas('pms_notifications', [
            'user_id' => $other->id,
            'type'    => 'assign',
        ]);
    });

    it('deletes an issue (soft delete)', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue = Issue::factory()->create([
            'project_id'  => $project->id,
            'status_id'   => $status->id,
            'reporter_id' => $user->id,
            'key'         => 'TST-1',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/issues/{$issue->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('issues', ['id' => $issue->id]);
    });

    it('duplicates an issue', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue = Issue::factory()->create([
            'project_id'  => $project->id,
            'status_id'   => $status->id,
            'reporter_id' => $user->id,
            'key'         => 'TST-1',
            'title'       => 'Original',
        ]);

        $res = $this->withToken($token)
            ->postJson("/api/issues/{$issue->id}/duplicate")
            ->assertStatus(201)
            ->assertJsonPath('data.key', 'TST-2');

        expect($res->json('data.title'))->toContain('Copy of');
    });

    it('filters issues by priority', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1', 'priority' => 'high']);
        Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-2', 'priority' => 'low']);

        $res = $this->withToken($token)
            ->getJson("/api/projects/{$project->id}/issues?priority=high")
            ->assertOk();

        expect($res->json('data.total'))->toBe(1);
    });

    it('bulk updates issue status', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project    = createProject($workspace, $user);
        $todo       = defaultStatus($project);
        $doneStatus = $project->statuses()->where('name', 'Done')->first();

        $i1 = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $todo->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);
        $i2 = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $todo->id, 'reporter_id' => $user->id, 'key' => 'TST-2']);

        $this->withToken($token)
            ->postJson('/api/issues/bulk', [
                'ids'       => [$i1->id, $i2->id],
                'status_id' => $doneStatus->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.updated', 2);
    });
});
