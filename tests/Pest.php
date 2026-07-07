<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// ──────────────────────────────────────────────────────────────────────────────
// Global helpers available in all Feature tests
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Create a user, a workspace, make them a member, and return an authenticated
 * test client with the Sanctum token already set.
 */
function actingAsNewUser(array $userAttrs = []): array
{
    $user = User::factory()->create(array_merge([
        'avatar_initials' => 'TU',
        'avatar_color'    => '#4264f5',
    ], $userAttrs));

    $workspace = Workspace::create([
        'name'     => 'Test Workspace',
        'slug'     => 'test-workspace-'.uniqid(),
        'owner_id' => $user->id,
        'color'    => '#4264f5',
    ]);

    $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
    $user->update(['active_workspace_id' => $workspace->id]);

    $token = $user->createToken('test')->plainTextToken;

    return [$user, $workspace, $token];
}

/**
 * Create a project inside a workspace with default statuses.
 */
function createProject(Workspace $workspace, User $lead, array $attrs = []): Project
{
    $project = Project::create(array_merge([
        'workspace_id' => $workspace->id,
        'lead_id'      => $lead->id,
        'name'         => 'Test Project',
        'key'          => 'TST',
        'color'        => '#4264f5',
        'health'       => 'on-track',
        'progress'     => 0,
    ], $attrs));

    $statuses = [
        ['name' => 'To Do',       'color' => '#6b7280', 'icon' => '○', 'category' => 'todo',       'position' => 0, 'is_default' => true],
        ['name' => 'In Progress', 'color' => '#4264f5', 'icon' => '◑', 'category' => 'in_progress', 'position' => 1],
        ['name' => 'In Review',   'color' => '#f59e0b', 'icon' => '◕', 'category' => 'in_progress', 'position' => 2],
        ['name' => 'Done',        'color' => '#10b981', 'icon' => '●', 'category' => 'done',         'position' => 3],
    ];

    foreach ($statuses as $s) {
        $project->statuses()->create($s);
    }

    $project->members()->attach($lead->id, ['role' => 'owner']);

    return $project->fresh(['statuses']);
}

/**
 * Get the default "To Do" status for a project.
 */
function defaultStatus(Project $project): Status
{
    return $project->statuses()->where('is_default', true)->first()
        ?? $project->statuses()->orderBy('position')->first();
}
