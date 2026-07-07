<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repo,
        private readonly ActivityService $activity,
    ) {}

    public function list(Workspace $workspace, array $filters = []): Collection
    {
        return $this->repo->allForWorkspace($workspace->id, $filters);
    }

    public function find(int $id): Project
    {
        return $this->repo->findById($id);
    }

    public function create(Workspace $workspace, array $data, User $creator): Project
    {
        $data['workspace_id'] = $workspace->id;
        $data['key'] = strtoupper($data['key'] ?? Str::upper(Str::limit(Str::slug($data['name']), 5, '')));
        $data['lead_id'] = $data['lead_id'] ?? $creator->id;

        $project = $this->repo->create($data);

        // Creator is an owner member
        $this->repo->addMember($project, $creator->id, 'owner');

        $this->activity->log('project_created', $creator, project: $project, meta: [
            'name' => $project->name,
        ]);

        return $project;
    }

    public function update(Project $project, array $data): Project
    {
        return $this->repo->update($project, $data);
    }

    public function delete(Project $project): void
    {
        $this->repo->delete($project);
    }

    public function addMember(Project $project, int $userId, string $role = 'member'): void
    {
        $this->repo->addMember($project, $userId, $role);
    }

    public function removeMember(Project $project, int $userId): void
    {
        $this->repo->removeMember($project, $userId);
    }

    public function toggleFavorite(Project $project, User $user): bool
    {
        return $this->repo->toggleFavorite($project, $user->id);
    }

    public function members(Project $project): Collection
    {
        return $this->repo->getMembers($project);
    }
}
