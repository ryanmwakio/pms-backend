<?php

namespace App\Http\Controllers\Api;

use App\Models\Label;
use App\Models\Workspace;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LabelController extends BaseController
{
    public function __construct(private readonly LabelService $service) {}

    #[OA\Get(
        path: '/labels',
        operationId: 'listLabels',
        summary: 'List labels in workspace',
        description: 'Returns a list of all labels in the current workspace.',
        tags: ['Labels']
    )]
    #[OA\Response(
        response: 200,
        description: 'Labels retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function index(): JsonResponse
    {
        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->ok($this->service->list($workspace));
    }

    #[OA\Post(
        path: '/labels',
        operationId: 'createLabel',
        summary: 'Create a new label',
        description: 'Creates a new label in the current workspace.',
        tags: ['Labels']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'The name of the label'),
                new OA\Property(property: 'color', type: 'string', description: 'Color for the label'),
                new OA\Property(property: 'description', type: 'string', description: 'Description of the label'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Label created successfully'
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
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:60'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
        ]);

        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->created($this->service->create($workspace, $data));
    }

    #[OA\Get(
        path: '/labels/{label}',
        operationId: 'getLabel',
        summary: 'Get label details',
        description: 'Returns details of a specific label.',
        tags: ['Labels']
    )]
    #[OA\Parameter(
        name: 'label',
        in: 'path',
        required: true,
        description: 'The ID of the label',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Label retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function show(Label $label): JsonResponse
    {
        return $this->ok($label);
    }

    #[OA\Put(
        path: '/labels/{label}',
        operationId: 'updateLabel',
        summary: 'Update a label',
        description: 'Updates an existing label.',
        tags: ['Labels']
    )]
    #[OA\Parameter(
        name: 'label',
        in: 'path',
        required: true,
        description: 'The ID of the label',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'The name of the label'),
                new OA\Property(property: 'color', type: 'string', description: 'Color for the label'),
                new OA\Property(property: 'description', type: 'string', description: 'Description of the label'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Label updated successfully'
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
    public function update(Request $request, Label $label): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:60'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
        ]);

        return $this->ok($this->service->update($label, $data));
    }

    #[OA\Delete(
        path: '/labels/{label}',
        operationId: 'deleteLabel',
        summary: 'Delete a label',
        description: 'Deletes an existing label.',
        tags: ['Labels']
    )]
    #[OA\Parameter(
        name: 'label',
        in: 'path',
        required: true,
        description: 'The ID of the label',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Label deleted successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function destroy(Label $label): JsonResponse
    {
        $this->service->delete($label);

        return $this->noContent();
    }
}
