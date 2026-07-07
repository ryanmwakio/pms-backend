<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function allForWorkspace(int $workspaceId, array $filters = []): Collection;

    public function allForUser(User $user, int $workspaceId): Collection;

    public function findById(int $id): Project;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;

    public function addMember(Project $project, int $userId, string $role = 'member'): void;

    public function removeMember(Project $project, int $userId): void;

    public function toggleFavorite(Project $project, int $userId): bool;

    public function getMembers(Project $project): Collection;
}
