<?php

namespace App\Repositories\Contracts;

use App\Models\Epic;
use Illuminate\Database\Eloquent\Collection;

interface EpicRepositoryInterface
{
    public function allForProject(int $projectId): Collection;

    public function findById(int $id): Epic;

    public function create(array $data): Epic;

    public function update(Epic $epic, array $data): Epic;

    public function delete(Epic $epic): void;
}
