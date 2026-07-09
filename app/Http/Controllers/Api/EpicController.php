<?php

namespace App\Http\Controllers\Api;

use App\Models\Epic;
use App\Models\Project;
use App\Services\EpicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EpicController extends BaseController
{
    public function __construct(private readonly EpicService $service) {}

    #[OA\Get(
        path: '/projects/{project}/epics',
        operationId: 'listEpics',
        summary: 'List epics for a project',
        description: 'Returns a list of all epics in the specified project.',
        tags: ['Epics']
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
        description: 'Epics retrieved successfully',
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
        path: '/projects/{project}/epics',
        operationId: 'createEpic',
        summary: 'Create a new epic',
        description: 'Creates a new epic in the specified project.',
        tags: ['Epics']
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
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', description: 'The title of the epic'),
                new OA\Property(property: 'description', type: 'string', description: 'The description of the epic'),
                new OA\Property(property: 'color', type: 'string', description: 'Color for the epic'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Start date'),
                new OA\Property(property: 'due_date', type: 'string', format: 'date', description: 'Due date'),
                new OA\Property(property: 'assignee_id', type: 'integer', description: 'ID of the assignee user'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Epic created successfully',
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
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'start_date'  => ['sometimes', 'nullable', 'date'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        return $this->created($this->service->create($project, $data));
    }

    #[OA\Get(
        path: '/epics/{epic}',
        operationId: 'getEpic',
        summary: 'Get epic details',
        description: 'Returns details of a specific epic.',
        tags: ['Epics']
    )]
    #[OA\Parameter(
        name: 'epic',
        in: 'path',
        required: true,
        description: 'The ID of the epic',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Epic retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function show(Epic $epic): JsonResponse
    {
        return $this->ok($this->service->find($epic->id));
    }

    #[OA\Put(
        path: '/epics/{epic}',
        operationId: 'updateEpic',
        summary: 'Update an epic',
        description: 'Updates an existing epic.',
        tags: ['Epics']
    )]
    #[OA\Parameter(
        name: 'epic',
        in: 'path',
        required: true,
        description: 'The ID of the epic',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string', description: 'The title of the epic'),
                new OA\Property(property: 'description', type: 'string', description: 'The description of the epic'),
                new OA\Property(property: 'color', type: 'string', description: 'Color for the epic'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Start date'),
                new OA\Property(property: 'due_date', type: 'string', format: 'date', description: 'Due date'),
                new OA\Property(property: 'assignee_id', type: 'integer', description: 'ID of the assignee user'),
                new OA\Property(property: 'position', type: 'integer', description: 'Position in list'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Epic updated successfully'
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
    public function update(Request $request, Epic $epic): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'start_date'  => ['sometimes', 'nullable', 'date'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'position'    => ['sometimes', 'integer', 'min:0'],
        ]);

        return $this->ok($this->service->update($epic, $data));
    }

    #[OA\Delete(
        path: '/epics/{epic}',
        operationId: 'deleteEpic',
        summary: 'Delete an epic',
        description: 'Deletes an existing epic.',
        tags: ['Epics']
    )]
    #[OA\Parameter(
        name: 'epic',
        in: 'path',
        required: true,
        description: 'The ID of the epic',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Epic deleted successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function destroy(Epic $epic): JsonResponse
    {
        $this->service->delete($epic);

        return $this->noContent();
    }
}
