<?php

namespace App\Repositories;

use App\Models\Issue;
use App\Repositories\Contracts\IssueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IssueRepository implements IssueRepositoryInterface
{
    private function baseQuery(int $projectId)
    {
        return Issue::forProject($projectId)
            ->with(['status', 'assignee', 'reporter', 'labels', 'epic', 'sprint', 'parent'])
            ->withCount(['comments', 'children as subtask_count']);
    }

    public function allForProject(int $projectId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery($projectId);
        $this->applyFilters($query, $filters);

        return $query->orderBy('position')->paginate($filters['per_page'] ?? 50);
    }

    public function forBoard(int $projectId, array $filters = []): Collection
    {
        $query = $this->baseQuery($projectId);
        $this->applyFilters($query, $filters);

        if (isset($filters['sprint_id'])) {
            $query->where('sprint_id', $filters['sprint_id']);
        }

        return $query->orderBy('position')->get();
    }

    public function backlog(int $projectId, array $filters = []): Collection
    {
        $query = $this->baseQuery($projectId)->backlog();
        $this->applyFilters($query, $filters);

        return $query->orderBy('position')->get();
    }

    public function findById(int $id): Issue
    {
        return Issue::with([
            'status', 'assignee', 'reporter', 'labels', 'epic', 'sprint',
            'parent', 'children.status', 'children.assignee',
            'watchers', 'linkedIssues.status',
            'comments.user', 'comments.reactions', 'comments.replies.user',
            'attachments.uploader',
            'activities.user',
        ])->findOrFail($id);
    }

    public function create(array $data): Issue
    {
        $labelIds = $data['label_ids'] ?? [];
        $watcherIds = $data['watcher_ids'] ?? [];
        unset($data['label_ids'], $data['watcher_ids']);

        $issue = Issue::create($data);

        if ($labelIds) {
            $issue->labels()->sync($labelIds);
        }

        if ($watcherIds) {
            $issue->watchers()->sync($watcherIds);
        }

        return $this->findById($issue->id);
    }

    public function update(Issue $issue, array $data): Issue
    {
        $labelIds = $data['label_ids'] ?? null;
        $watcherIds = $data['watcher_ids'] ?? null;
        unset($data['label_ids'], $data['watcher_ids']);

        $issue->update($data);

        if ($labelIds !== null) {
            $issue->labels()->sync($labelIds);
        }

        if ($watcherIds !== null) {
            $issue->watchers()->sync($watcherIds);
        }

        return $this->findById($issue->id);
    }

    public function delete(Issue $issue): void
    {
        $issue->delete();
    }

    public function duplicate(Issue $issue): Issue
    {
        $new = $issue->replicate(['key', 'sprint_id', 'started_at', 'completed_at']);
        $new->key = $issue->project->nextIssueKey();
        $new->title = 'Copy of '.$issue->title;
        $new->save();

        // Copy labels
        $issue->labels->each(fn ($l) => $new->labels()->attach($l->id));

        return $this->findById($new->id);
    }

    public function moveToSprint(Issue $issue, ?int $sprintId): void
    {
        $issue->update(['sprint_id' => $sprintId]);
    }

    public function bulkUpdate(array $ids, array $data): int
    {
        return Issue::whereIn('id', $ids)->update($data);
    }

    public function addWatcher(Issue $issue, int $userId): void
    {
        $issue->watchers()->syncWithoutDetaching([$userId]);
    }

    public function removeWatcher(Issue $issue, int $userId): void
    {
        $issue->watchers()->detach($userId);
    }

    public function addLink(Issue $issue, int $linkedId, string $type): void
    {
        $issue->linkedIssues()->syncWithoutDetaching([
            $linkedId => ['link_type' => $type],
        ]);
    }

    public function removeLink(Issue $issue, int $linkedId): void
    {
        $issue->linkedIssues()->detach($linkedId);
    }

    public function reorder(int $projectId, int $statusId, array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            Issue::where('id', $id)
                ->where('project_id', $projectId)
                ->update(['status_id' => $statusId, 'position' => $position]);
        }
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['epic_id'])) {
            $query->where('epic_id', $filters['epic_id']);
        }

        if (! empty($filters['label_ids'])) {
            $query->whereHas('labels', fn ($q) => $q->whereIn('label_id', (array) $filters['label_ids']));
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%'.$filters['search'].'%')
                    ->orWhere('key', 'like', '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['due_before'])) {
            $query->whereDate('due_date', '<=', $filters['due_before']);
        }

        if (! empty($filters['due_after'])) {
            $query->whereDate('due_date', '>=', $filters['due_after']);
        }
    }
}
