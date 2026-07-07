<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\SprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SprintController extends BaseController
{
    public function __construct(private readonly SprintService $service) {}

    public function index(Project $project): JsonResponse
    {
        return $this->ok($this->service->list($project));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'goal'       => ['sometimes', 'nullable', 'string', 'max:500'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date'   => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return $this->created($this->service->create($project, $data));
    }

    public function show(Sprint $sprint): JsonResponse
    {
        return $this->ok($this->service->find($sprint->id));
    }

    public function update(Request $request, Sprint $sprint): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:100'],
            'goal'       => ['sometimes', 'nullable', 'string', 'max:500'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date'   => ['sometimes', 'nullable', 'date'],
        ]);

        return $this->ok($this->service->update($sprint, $data));
    }

    public function destroy(Sprint $sprint): JsonResponse
    {
        $this->service->delete($sprint);

        return $this->noContent();
    }

    public function start(Request $request, Sprint $sprint): JsonResponse
    {
        return $this->ok($this->service->start($sprint, $request->user()));
    }

    public function complete(Request $request, Sprint $sprint): JsonResponse
    {
        $data = $request->validate([
            'carry_over_sprint_id' => ['sometimes', 'nullable', 'integer', 'exists:sprints,id'],
        ]);

        return $this->ok(
            $this->service->complete($sprint, $request->user(), $data['carry_over_sprint_id'] ?? null)
        );
    }

    public function addIssue(Sprint $sprint, Issue $issue): JsonResponse
    {
        $this->service->addIssue($sprint, $issue->id);

        return $this->ok(null, 'Issue added to sprint');
    }

    public function removeIssue(Sprint $sprint, Issue $issue): JsonResponse
    {
        $this->service->removeIssue($sprint, $issue->id);

        return $this->noContent();
    }
}
