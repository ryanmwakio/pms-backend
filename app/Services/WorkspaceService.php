<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class WorkspaceService
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $repo,
    ) {}

    public function list(User $user): Collection
    {
        return $this->repo->allForUser($user);
    }

    public function find(int $id): Workspace
    {
        return $this->repo->findById($id);
    }

    public function create(array $data, User $owner): Workspace
    {
        $data['owner_id'] = $owner->id;
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);

        $workspace = $this->repo->create($data);

        // Set as user's active workspace if they don't have one
        if (! $owner->active_workspace_id) {
            $owner->update(['active_workspace_id' => $workspace->id]);
        }

        return $workspace;
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        return $this->repo->update($workspace, $data);
    }

    public function delete(Workspace $workspace): void
    {
        $this->repo->delete($workspace);
    }

    public function addMember(Workspace $workspace, int $userId, string $role = 'member'): void
    {
        $this->repo->addMember($workspace, $userId, $role);
    }

    public function removeMember(Workspace $workspace, int $userId): void
    {
        $this->repo->removeMember($workspace, $userId);
    }

    public function switchWorkspace(User $user, Workspace $workspace): void
    {
        $user->update(['active_workspace_id' => $workspace->id]);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;

        while (Workspace::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
