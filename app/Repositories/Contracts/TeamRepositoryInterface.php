<?php

namespace App\Repositories\Contracts;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

interface TeamRepositoryInterface
{
    public function allForWorkspace(int $workspaceId): Collection;

    public function findById(int $id): Team;

    public function create(array $data): Team;

    public function update(Team $team, array $data): Team;

    public function delete(Team $team): void;

    public function addMember(Team $team, int $userId, string $role = 'member'): void;

    public function removeMember(Team $team, int $userId): void;
}
