<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\SprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SprintController extends BaseController
{
    public function __construct(private readonly SprintService $service) {}

    #[OA\Get(
        path: '/projects/{project}/sprints',
        operationId: 'listSprints',
        summary: 'List sprints for a project',
        description: 'Returns a list of all sprints in the specified project.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'project',
        in: 'path',
        required: true,
        description: 'The ID of the project',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Sprints retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function index(Project $project): JsonResponse
    {
        return $this->ok($this->service->list($project));
    }

    #[OA\Post(
        path: '/projects/{project}/sprints',
        operationId: 'createSprint',
        summary: 'Create a new sprint',
        description: 'Creates a new sprint in the specified project.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'project',
        in: 'path',
        required: true,
        description: 'The ID of the project',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'The name of the sprint'),
                new OA\Property(property: 'goal', type: 'string', description: 'The goal of the sprint'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Start date'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'End date'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Sprint created successfully'
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
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

    #[OA\Get(
        path: '/sprints/{sprint}',
        operationId: 'getSprint',
        summary: 'Get sprint details',
        description: 'Returns details of a specific sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Sprint retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function show(Sprint $sprint): JsonResponse
    {
        return $this->ok($this->service->find($sprint->id));
    }

    #[OA\Put(
        path: '/sprints/{sprint}',
        operationId: 'updateSprint',
        summary: 'Update a sprint',
        description: 'Updates an existing sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'The name of the sprint'),
                new OA\Property(property: 'goal', type: 'string', description: 'The goal of the sprint'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Start date'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', description: 'End date'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Sprint updated successfully',
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
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

    #[OA\Delete(
        path: '/sprints/{sprint}',
        operationId: 'deleteSprint',
        summary: 'Delete a sprint',
        description: 'Deletes an existing sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Sprint deleted successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function destroy(Sprint $sprint): JsonResponse
    {
        $this->service->delete($sprint);

        return $this->noContent();
    }

    #[OA\Post(
        path: '/sprints/{sprint}/start',
        operationId: 'startSprint',
        summary: 'Start a sprint',
        description: 'Starts the sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Sprint started successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function start(Request $request, Sprint $sprint): JsonResponse
    {
        return $this->ok($this->service->start($sprint, $request->user()));
    }

    #[OA\Post(
        path: '/sprints/{sprint}/complete',
        operationId: 'completeSprint',
        summary: 'Complete a sprint',
        description: 'Completes the sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'carry_over_sprint_id', type: 'integer', description: 'ID of the sprint to carry over issues to'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Sprint completed successfully'
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function complete(Request $request, Sprint $sprint): JsonResponse
    {
        $data = $request->validate([
            'carry_over_sprint_id' => ['sometimes', 'nullable', 'integer', 'exists:sprints,id'],
        ]);

        return $this->ok(
            $this->service->complete($sprint, $request->user(), $data['carry_over_sprint_id'] ?? null)
        );
    }

    #[OA\Post(
        path: '/sprints/{sprint}/issues/{issue}',
        operationId: 'addIssueToSprint',
        summary: 'Add issue to sprint',
        description: 'Adds an issue to the sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'issue',
        in: 'path',
        required: true,
        description: 'The ID of the issue',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Issue added to sprint'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function addIssue(Sprint $sprint, Issue $issue): JsonResponse
    {
        $this->service->addIssue($sprint, $issue->id);

        return $this->ok(null, 'Issue added to sprint');
    }

    #[OA\Delete(
        path: '/sprints/{sprint}/issues/{issue}',
        operationId: 'removeIssueFromSprint',
        summary: 'Remove issue from sprint',
        description: 'Removes an issue from the sprint.',
        tags: ['Sprints']
    )]
    #[OA\Parameter(
        name: 'sprint',
        in: 'path',
        required: true,
        description: 'The ID of the sprint',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'issue',
        in: 'path',
        required: true,
        description: 'The ID of the issue',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Issue removed from sprint'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function removeIssue(Sprint $sprint, Issue $issue): JsonResponse
    {
        $this->service->removeIssue($sprint, $issue->id);

        return $this->noContent();
    }
}
