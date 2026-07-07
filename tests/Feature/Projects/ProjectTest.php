<?php

use App\Models\Project;
use App\Models\User;

describe('Projects', function () {

    it('lists projects for the active workspace', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        createProject($workspace, $user, ['name' => 'Alpha']);
        createProject($workspace, $user, ['name' => 'Beta', 'key' => 'BET']);

        $this->withToken($token)
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('creates a project with default statuses', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        $res = $this->withToken($token)
            ->postJson('/api/projects', [
                'name'  => 'New Project',
                'key'   => 'NP',
                'color' => '#10b981',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.name', 'New Project')
            ->assertJsonPath('data.key', 'NP');

        $this->assertDatabaseHas('projects', ['key' => 'NP', 'workspace_id' => $workspace->id]);

        // Should have default statuses
        $project = Project::where('key', 'NP')->first();
        expect($project->statuses()->count())->toBe(5);
    });

    it('shows a project with statuses and epics', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $this->withToken($token)
            ->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'statuses']]);
    });

    it('updates a project', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $this->withToken($token)
            ->putJson("/api/projects/{$project->id}", ['health' => 'at-risk', 'progress' => 45])
            ->assertOk()
            ->assertJsonPath('data.health', 'at-risk')
            ->assertJsonPath('data.progress', 45);
    });

    it('deletes a project', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $this->withToken($token)
            ->deleteJson("/api/projects/{$project->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    });

    it('adds and removes a member', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $other   = User::factory()->create();
        $workspace->members()->attach($other->id, ['role' => 'member', 'joined_at' => now()]);

        $this->withToken($token)
            ->postJson("/api/projects/{$project->id}/members", ['user_id' => $other->id])
            ->assertOk();

        $this->assertDatabaseHas('project_members', ['project_id' => $project->id, 'user_id' => $other->id]);

        $this->withToken($token)
            ->deleteJson("/api/projects/{$project->id}/members/{$other->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('project_members', ['project_id' => $project->id, 'user_id' => $other->id]);
    });

    it('toggles a project as favourite', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);

        $res = $this->withToken($token)
            ->postJson("/api/projects/{$project->id}/favorite");

        $res->assertOk()->assertJsonPath('data.is_favorite', true);

        // Toggle back
        $this->withToken($token)
            ->postJson("/api/projects/{$project->id}/favorite")
            ->assertJsonPath('data.is_favorite', false);
    });
});
