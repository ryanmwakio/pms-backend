<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\IssueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IssueService
{
    public function __construct(
        private readonly IssueRepositoryInterface $repo,
        private readonly ActivityService $activity,
        private readonly NotificationService $notifications,
    ) {}

    public function listForBoard(Project $project, array $filters = []): Collection
    {
        return $this->repo->forBoard($project->id, $filters);
    }

    public function list(Project $project, array $filters = []): LengthAwarePaginator
    {
        return $this->repo->allForProject($project->id, $filters);
    }

    public function backlog(Project $project, array $filters = []): Collection
    {
        return $this->repo->backlog($project->id, $filters);
    }

    public function find(int $id): Issue
    {
        return $this->repo->findById($id);
    }

    public function create(Project $project, array $data, User $creator): Issue
    {
        return DB::transaction(function () use ($project, $data, $creator) {
            // Auto-assign default status if not provided
            if (empty($data['status_id'])) {
                $default = $project->statuses()->where('is_default', true)->first()
                    ?? $project->statuses()->orderBy('position')->first();

                $data['status_id'] = $default?->id;
            }

            $data['project_id'] = $project->id;
            $data['reporter_id'] = $creator->id;
            $data['key'] = $project->nextIssueKey();

            $issue = $this->repo->create($data);

            $this->activity->logIssueCreated($issue, $creator);

            // Auto-watch: reporter and assignee
            $this->repo->addWatcher($issue, $creator->id);
            if (! empty($data['assignee_id']) && $data['assignee_id'] !== $creator->id) {
                $this->repo->addWatcher($issue, $data['assignee_id']);
                $assignee = User::find($data['assignee_id']);
                if ($assignee) {
                    $this->notifications->notifyAssigned($issue, $creator, $assignee);
                }
            }

            return $issue;
        });
    }

    public function update(Issue $issue, array $data, User $updater): Issue
    {
        return DB::transaction(function () use ($issue, $data, $updater) {
            $oldStatus   = $issue->status?->name;
            $oldPriority = $issue->priority;
            $oldAssignee = $issue->assignee_id;

            $updated = $this->repo->update($issue, $data);

            // Log status change
            if (isset($data['status_id']) && $data['status_id'] !== $issue->status_id) {
                $newStatus = $updated->status?->name ?? 'Unknown';
                $this->activity->logStatusChanged($updated, $updater, $oldStatus ?? '', $newStatus);
                $this->notifications->notifyStatusChange($updated, $updater, $newStatus);
            }

            // Log priority change
            if (isset($data['priority']) && $data['priority'] !== $oldPriority) {
                $this->activity->logPriorityChanged($updated, $updater, $oldPriority, $data['priority']);
            }

            // Log assignment change
            if (isset($data['assignee_id']) && $data['assignee_id'] !== $oldAssignee) {
                $assignee = $data['assignee_id'] ? User::find($data['assignee_id']) : null;
                $this->activity->logAssigned($updated, $updater, $assignee);
                if ($assignee) {
                    $this->notifications->notifyAssigned($updated, $updater, $assignee);
                    $this->repo->addWatcher($updated, $assignee->id);
                }
            }

            return $updated;
        });
    }

    public function delete(Issue $issue): void
    {
        $this->repo->delete($issue);
    }

    public function duplicate(Issue $issue, User $actor): Issue
    {
        $copy = $this->repo->duplicate($issue);
        $this->activity->logIssueCreated($copy, $actor);

        return $copy;
    }

    public function moveToSprint(Issue $issue, ?int $sprintId, User $actor): void
    {
        $from = $issue->sprint?->name;
        $this->repo->moveToSprint($issue, $sprintId);
        $to = $sprintId ? \App\Models\Sprint::find($sprintId)?->name : null;
        $this->activity->logSprintChanged($issue, $actor, $from, $to);
    }

    public function bulk(array $ids, array $data, User $actor): int
    {
        return $this->repo->bulkUpdate($ids, $data);
    }

    public function reorder(Project $project, int $statusId, array $orderedIds): void
    {
        $this->repo->reorder($project->id, $statusId, $orderedIds);
    }
}
