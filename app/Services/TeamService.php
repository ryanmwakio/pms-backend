<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Workspace;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TeamService
{
    public function __construct(
        private readonly TeamRepositoryInterface $repo,
    ) {}

    public function list(Workspace $workspace): Collection
    {
        return $this->repo->allForWorkspace($workspace->id);
    }

    public function find(int $id): Team
    {
        return $this->repo->findById($id);
    }

    public function create(Workspace $workspace, array $data): Team
    {
        $data['workspace_id'] = $workspace->id;

        $team = $this->repo->create($data);

        // Auto-add lead as member
        if (! empty($data['lead_id'])) {
            $this->repo->addMember($team, $data['lead_id'], 'lead');
        }

        return $team;
    }

    public function update(Team $team, array $data): Team
    {
        return $this->repo->update($team, $data);
    }

    public function delete(Team $team): void
    {
        $this->repo->delete($team);
    }

    public function addMember(Team $team, int $userId, string $role = 'member'): void
    {
        $this->repo->addMember($team, $userId, $role);
    }

    public function removeMember(Team $team, int $userId): void
    {
        $this->repo->removeMember($team, $userId);
    }
}
