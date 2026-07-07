<?php

namespace App\Repositories\Contracts;

use App\Models\Issue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface IssueRepositoryInterface
{
    public function allForProject(int $projectId, array $filters = []): LengthAwarePaginator;

    public function forBoard(int $projectId, array $filters = []): Collection;

    public function backlog(int $projectId, array $filters = []): Collection;

    public function findById(int $id): Issue;

    public function create(array $data): Issue;

    public function update(Issue $issue, array $data): Issue;

    public function delete(Issue $issue): void;

    public function duplicate(Issue $issue): Issue;

    public function moveToSprint(Issue $issue, ?int $sprintId): void;

    public function bulkUpdate(array $ids, array $data): int;

    public function addWatcher(Issue $issue, int $userId): void;

    public function removeWatcher(Issue $issue, int $userId): void;

    public function addLink(Issue $issue, int $linkedId, string $type): void;

    public function removeLink(Issue $issue, int $linkedId): void;

    public function reorder(int $projectId, int $statusId, array $orderedIds): void;
}
