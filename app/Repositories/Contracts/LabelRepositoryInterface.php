<?php

namespace App\Repositories\Contracts;

use App\Models\Label;
use Illuminate\Database\Eloquent\Collection;

interface LabelRepositoryInterface
{
    public function allForWorkspace(int $workspaceId): Collection;

    public function findById(int $id): Label;

    public function create(array $data): Label;

    public function update(Label $label, array $data): Label;

    public function delete(Label $label): void;
}
