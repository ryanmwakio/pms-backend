<?php

use App\Models\Team;
use App\Models\User;

describe('Teams', function () {

    it('lists teams in the workspace', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        Team::factory()->count(3)->create(['workspace_id' => $workspace->id]);

        $this->withToken($token)
            ->getJson('/api/teams')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('creates a team with the lead as a member', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        $res = $this->withToken($token)
            ->postJson('/api/teams', [
                'name'    => 'Engineering',
                'color'   => '#4264f5',
                'lead_id' => $user->id,
            ]);

        $res->assertStatus(201)->assertJsonPath('data.name', 'Engineering');

        $team = Team::where('name', 'Engineering')->first();
        $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'user_id' => $user->id, 'role' => 'lead']);
    });

    it('updates a team', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $team = Team::factory()->create(['workspace_id' => $workspace->id]);

        $this->withToken($token)
            ->putJson("/api/teams/{$team->id}", ['name' => 'Renamed Team', 'color' => '#ef4444'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Team');
    });

    it('adds a member to a team', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $team  = Team::factory()->create(['workspace_id' => $workspace->id]);
        $other = User::factory()->create();
        $workspace->members()->attach($other->id, ['role' => 'member', 'joined_at' => now()]);

        $this->withToken($token)
            ->postJson("/api/teams/{$team->id}/members", ['user_id' => $other->id])
            ->assertOk();

        $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'user_id' => $other->id]);
    });

    it('removes a member from a team', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $team  = Team::factory()->create(['workspace_id' => $workspace->id]);
        $other = User::factory()->create();
        $team->members()->attach($other->id, ['role' => 'member']);

        $this->withToken($token)
            ->deleteJson("/api/teams/{$team->id}/members/{$other->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $other->id]);
    });

    it('deletes a team', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $team = Team::factory()->create(['workspace_id' => $workspace->id]);

        $this->withToken($token)
            ->deleteJson("/api/teams/{$team->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    });
});
