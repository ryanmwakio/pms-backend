<?php

namespace App\Services;

use App\Models\Label;
use App\Models\Workspace;
use App\Repositories\Contracts\LabelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LabelService
{
    public function __construct(
        private readonly LabelRepositoryInterface $repo,
    ) {}

    public function list(Workspace $workspace): Collection
    {
        return $this->repo->allForWorkspace($workspace->id);
    }

    public function find(int $id): Label
    {
        return $this->repo->findById($id);
    }

    public function create(Workspace $workspace, array $data): Label
    {
        $data['workspace_id'] = $workspace->id;

        return $this->repo->create($data);
    }

    public function update(Label $label, array $data): Label
    {
        return $this->repo->update($label, $data);
    }

    public function delete(Label $label): void
    {
        $this->repo->delete($label);
    }
}
