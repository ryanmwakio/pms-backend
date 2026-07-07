<?php

namespace App\Services;

use App\Models\Epic;
use App\Models\Project;
use App\Repositories\Contracts\EpicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EpicService
{
    public function __construct(
        private readonly EpicRepositoryInterface $repo,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->repo->allForProject($project->id);
    }

    public function find(int $id): Epic
    {
        return $this->repo->findById($id);
    }

    public function create(Project $project, array $data): Epic
    {
        $data['project_id'] = $project->id;
        $data['position'] = $this->repo->allForProject($project->id)->count();

        return $this->repo->create($data);
    }

    public function update(Epic $epic, array $data): Epic
    {
        return $this->repo->update($epic, $data);
    }

    public function delete(Epic $epic): void
    {
        $this->repo->delete($epic);
    }
}
