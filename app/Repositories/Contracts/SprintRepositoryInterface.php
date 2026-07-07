<?php

namespace App\Repositories\Contracts;

use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

interface SprintRepositoryInterface
{
    public function allForProject(int $projectId): Collection;

    public function findById(int $id): Sprint;

    public function create(array $data): Sprint;

    public function update(Sprint $sprint, array $data): Sprint;

    public function delete(Sprint $sprint): void;

    public function start(Sprint $sprint): Sprint;

    public function complete(Sprint $sprint, ?int $carryOverSprintId = null): Sprint;

    public function addIssue(Sprint $sprint, int $issueId): void;

    public function removeIssue(Sprint $sprint, int $issueId): void;

    public function activeForProject(int $projectId): ?Sprint;
}
