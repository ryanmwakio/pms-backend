<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function allForWorkspace(int $workspaceId, array $filters = []): Collection
    {
        $query = Project::where('workspace_id', $workspaceId)
            ->with(['lead', 'team', 'activeSprint'])
            ->withCount('issues');

        if (isset($filters['is_archived'])) {
            $query->where('is_archived', $filters['is_archived']);
        }

        if (isset($filters['health'])) {
            $query->where('health', $filters['health']);
        }

        return $query->orderBy('name')->get();
    }

    public function allForUser(User $user, int $workspaceId): Collection
    {
        return $user->projects()
            ->where('workspace_id', $workspaceId)
            ->with(['lead', 'team', 'activeSprint'])
            ->withCount('issues')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): Project
    {
        return Project::with([
            'lead',
            'team',
            'activeSprint',
            'statuses',
            'epics',
        ])->findOrFail($id);
    }

    public function create(array $data): Project
    {
        $project = Project::create($data);

        // Auto-create default statuses
        $defaults = [
            ['name' => 'To Do',      'color' => '#6b7280', 'icon' => '○', 'category' => 'todo',        'position' => 0, 'is_default' => true],
            ['name' => 'In Progress','color' => '#4264f5', 'icon' => '◑', 'category' => 'in_progress',  'position' => 1],
            ['name' => 'In Review',  'color' => '#f59e0b', 'icon' => '◕', 'category' => 'in_progress',  'position' => 2],
            ['name' => 'Done',       'color' => '#10b981', 'icon' => '●', 'category' => 'done',          'position' => 3],
            ['name' => 'Blocked',    'color' => '#ef4444', 'icon' => '⊗', 'category' => 'in_progress',  'position' => 4],
        ];

        foreach ($defaults as $status) {
            $project->statuses()->create($status);
        }

        return $project->fresh(['statuses']);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function addMember(Project $project, int $userId, string $role = 'member'): void
    {
        $project->members()->syncWithoutDetaching([
            $userId => ['role' => $role],
        ]);
    }

    public function removeMember(Project $project, int $userId): void
    {
        $project->members()->detach($userId);
    }

    public function toggleFavorite(Project $project, int $userId): bool
    {
        $pivot = $project->members()->where('user_id', $userId)->first();

        if (! $pivot) {
            return false;
        }

        $current = (bool) $pivot->pivot->is_favorite;
        $project->members()->updateExistingPivot($userId, ['is_favorite' => ! $current]);

        return ! $current;
    }

    public function getMembers(Project $project): Collection
    {
        return $project->members()->get();
    }
}
