<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Models\Project;
use App\Services\IssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueController extends BaseController
{
    public function __construct(private readonly IssueService $service) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $filters = $request->only([
            'status_id', 'assignee_id', 'priority', 'type', 'epic_id',
            'label_ids', 'search', 'due_before', 'due_after',
            'sprint_id', 'per_page',
        ]);

        // Board view returns flat ungrouped collection for the kanban
        if ($request->boolean('board')) {
            return $this->ok($this->service->listForBoard($project, $filters));
        }

        return $this->ok($this->service->list($project, $filters));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:500'],
            'description'    => ['sometimes', 'nullable', 'string'],
            'type'           => ['sometimes', 'in:task,story,bug,epic,subtask'],
            'priority'       => ['sometimes', 'in:urgent,high,medium,low,none'],
            'status_id'      => ['sometimes', 'nullable', 'integer', 'exists:statuses,id'],
            'assignee_id'    => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'sprint_id'      => ['sometimes', 'nullable', 'integer', 'exists:sprints,id'],
            'epic_id'        => ['sometimes', 'nullable', 'integer', 'exists:epics,id'],
            'parent_id'      => ['sometimes', 'nullable', 'integer', 'exists:issues,id'],
            'story_points'   => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'time_estimate'  => ['sometimes', 'nullable', 'integer', 'min:0'],
            'due_date'       => ['sometimes', 'nullable', 'date'],
            'label_ids'      => ['sometimes', 'array'],
            'label_ids.*'    => ['integer', 'exists:labels,id'],
        ]);

        $issue = $this->service->create($project, $data, $request->user());

        return $this->created($issue);
    }

    public function show(Issue $issue): JsonResponse
    {
        return $this->ok($this->service->find($issue->id));
    }

    public function update(Request $request, Issue $issue): JsonResponse
    {
        $data = $request->validate([
            'title'         => ['sometimes', 'string', 'max:500'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'type'          => ['sometimes', 'in:task,story,bug,epic,subtask'],
            'priority'      => ['sometimes', 'in:urgent,high,medium,low,none'],
            'status_id'     => ['sometimes', 'integer', 'exists:statuses,id'],
            'assignee_id'   => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'sprint_id'     => ['sometimes', 'nullable', 'integer', 'exists:sprints,id'],
            'epic_id'       => ['sometimes', 'nullable', 'integer', 'exists:epics,id'],
            'parent_id'     => ['sometimes', 'nullable', 'integer', 'exists:issues,id'],
            'story_points'  => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'time_estimate' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'time_spent'    => ['sometimes', 'nullable', 'integer', 'min:0'],
            'due_date'      => ['sometimes', 'nullable', 'date'],
            'label_ids'     => ['sometimes', 'array'],
            'label_ids.*'   => ['integer', 'exists:labels,id'],
            'position'      => ['sometimes', 'integer', 'min:0'],
        ]);

        return $this->ok($this->service->update($issue, $data, $request->user()));
    }

    public function destroy(Issue $issue): JsonResponse
    {
        $this->service->delete($issue);

        return $this->noContent();
    }

    public function duplicate(Request $request, Issue $issue): JsonResponse
    {
        return $this->created($this->service->duplicate($issue, $request->user()));
    }

    public function move(Request $request, Issue $issue): JsonResponse
    {
        $data = $request->validate([
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
        ]);

        $this->service->moveToSprint($issue, $data['sprint_id'], $request->user());

        return $this->ok(null, 'Issue moved');
    }

    public function addWatcher(Request $request, Issue $issue): JsonResponse
    {
        $userId = $request->input('user_id', $request->user()->id);
        $this->service->find($issue->id); // ensure loaded
        app(\App\Repositories\Contracts\IssueRepositoryInterface::class)->addWatcher($issue, $userId);

        return $this->ok(null, 'Watcher added');
    }

    public function removeWatcher(Request $request, Issue $issue): JsonResponse
    {
        $userId = $request->input('user_id', $request->user()->id);
        app(\App\Repositories\Contracts\IssueRepositoryInterface::class)->removeWatcher($issue, $userId);

        return $this->noContent();
    }

    public function addLink(Request $request, Issue $issue): JsonResponse
    {
        $data = $request->validate([
            'linked_issue_id' => ['required', 'integer', 'exists:issues,id'],
            'link_type'       => ['required', 'in:blocks,is_blocked_by,relates_to,duplicates,is_duplicated_by'],
        ]);

        app(\App\Repositories\Contracts\IssueRepositoryInterface::class)
            ->addLink($issue, $data['linked_issue_id'], $data['link_type']);

        return $this->ok(null, 'Link added');
    }

    public function removeLink(Issue $issue, Issue $linked): JsonResponse
    {
        app(\App\Repositories\Contracts\IssueRepositoryInterface::class)->removeLink($issue, $linked->id);

        return $this->noContent();
    }

    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'          => ['required', 'array', 'min:1'],
            'ids.*'        => ['integer', 'exists:issues,id'],
            'status_id'    => ['sometimes', 'integer', 'exists:statuses,id'],
            'assignee_id'  => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'priority'     => ['sometimes', 'in:urgent,high,medium,low,none'],
            'sprint_id'    => ['sometimes', 'nullable', 'integer', 'exists:sprints,id'],
        ]);

        $ids  = $data['ids'];
        unset($data['ids']);

        $count = $this->service->bulk($ids, $data, $request->user());

        return $this->ok(['updated' => $count]);
    }

    public function activity(Issue $issue): JsonResponse
    {
        return $this->ok(
            $issue->activities()->with('user')->orderByDesc('created_at')->get()
        );
    }
}
