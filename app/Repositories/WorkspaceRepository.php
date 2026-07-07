<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WorkspaceRepository implements WorkspaceRepositoryInterface
{
    public function allForUser(User $user): Collection
    {
        return $user->workspaces()->with(['owner'])->get();
    }

    public function findById(int $id): Workspace
    {
        return Workspace::with(['owner'])->findOrFail($id);
    }

    public function findBySlug(string $slug): Workspace
    {
        return Workspace::where('slug', $slug)->firstOrFail();
    }

    public function create(array $data): Workspace
    {
        $workspace = Workspace::create($data);

        // Owner is automatically a member with 'owner' role
        $workspace->members()->attach($data['owner_id'], [
            'role'      => 'owner',
            'joined_at' => now(),
        ]);

        return $workspace->fresh();
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        $workspace->update($data);

        return $workspace->fresh();
    }

    public function delete(Workspace $workspace): void
    {
        $workspace->delete();
    }

    public function addMember(Workspace $workspace, int $userId, string $role = 'member'): void
    {
        $workspace->members()->syncWithoutDetaching([
            $userId => ['role' => $role, 'joined_at' => now()],
        ]);
    }

    public function removeMember(Workspace $workspace, int $userId): void
    {
        $workspace->members()->detach($userId);
    }

    public function isMember(Workspace $workspace, int $userId): bool
    {
        return $workspace->members()->where('user_id', $userId)->exists();
    }
}
