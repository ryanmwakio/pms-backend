<?php

namespace App\Repositories;

use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository implements TeamRepositoryInterface
{
    public function allForWorkspace(int $workspaceId): Collection
    {
        return Team::where('workspace_id', $workspaceId)
            ->with(['lead', 'members'])
            ->withCount('members')
            ->get();
    }

    public function findById(int $id): Team
    {
        return Team::with(['lead', 'members', 'projects'])->findOrFail($id);
    }

    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function update(Team $team, array $data): Team
    {
        $team->update($data);

        return $team->fresh();
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }

    public function addMember(Team $team, int $userId, string $role = 'member'): void
    {
        $team->members()->syncWithoutDetaching([
            $userId => ['role' => $role],
        ]);
    }

    public function removeMember(Team $team, int $userId): void
    {
        $team->members()->detach($userId);
    }
}
