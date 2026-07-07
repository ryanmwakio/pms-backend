<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Pagination\LengthAwarePaginator;

interface WorkspaceRepositoryInterface
{
    public function allForUser(User $user): \Illuminate\Database\Eloquent\Collection;

    public function findById(int $id): Workspace;

    public function findBySlug(string $slug): Workspace;

    public function create(array $data): Workspace;

    public function update(Workspace $workspace, array $data): Workspace;

    public function delete(Workspace $workspace): void;

    public function addMember(Workspace $workspace, int $userId, string $role = 'member'): void;

    public function removeMember(Workspace $workspace, int $userId): void;

    public function isMember(Workspace $workspace, int $userId): bool;
}
