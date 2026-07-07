<?php

use App\Models\User;

describe('Auth', function () {

    it('registers a new user and creates a default workspace', function () {
        $res = $this->postJson('/api/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $res->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['user', 'workspace', 'token'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('workspaces', ['owner_id' => $res->json('data.user.id')]);
    });

    it('rejects registration with duplicate email', function () {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/auth/register', [
            'name'                  => 'Jane',
            'email'                 => 'jane@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(422);
    });

    it('logs in with valid credentials', function () {
        [$user, $workspace, $token] = actingAsNewUser(['email' => 'test@example.com']);
        // Reset password to known value
        $user->update(['password' => bcrypt('secret123')]);

        $res = $this->postJson('/api/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'secret123',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    });

    it('rejects login with invalid credentials', function () {
        $this->postJson('/api/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    });

    it('returns the authenticated user on /me', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    });

    it('logs out and revokes the token', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        // Token should now be invalid
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    });

    it('updates user profile', function () {
        [$user, $workspace, $token] = actingAsNewUser();

        $this->withToken($token)
            ->putJson('/api/auth/me', ['name' => 'Updated Name', 'role' => 'CTO'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'CTO']);
    });

    it('requires authentication for protected routes', function () {
        $this->getJson('/api/projects')->assertStatus(401);
    });
});
