<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Workspace;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProjectController extends BaseController
{
    public function __construct(private readonly ProjectService $service) {}

    #[OA\Get(
        path: '/projects',
        operationId: 'listProjects',
        summary: 'List projects',
        description: 'Returns a list of projects in the current workspace.',
        tags: ['Projects']
    )]
    #[OA\Parameter(
        name: 'is_archived',
        in: 'query',
        description: 'Filter by archived status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'health',
        in: 'query',
        description: 'Filter by health status',
        schema: new OA\Schema(type: 'string', enum: ['on-track', 'at-risk', 'off-track'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Projects retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function index(Request $request): JsonResponse
    {
        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->ok($this->service->list($workspace, $request->only('is_archived', 'health')));
    }

    #[OA\Post(
        path: '/projects',
        operationId: 'createProject',
        summary: 'Create a new project',
        description: 'Creates a new project in the current workspace.',
        tags: ['Projects']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'The name of the project'),
                new OA\Property(property: 'key', type: 'string', description: 'The project key (e.g., PROJ)'),
                new OA\Property(property: 'description', type: 'string', description: 'The description of the project'),
                new OA\Property(property: 'color', type: 'string', description: 'Color for the project'),
                new OA\Property(property: 'team_id', type: 'integer', description: 'ID of the team'),
                new OA\Property(property: 'lead_id', type: 'integer', description: 'ID of the project lead'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Start date'),
                new OA\Property(property: 'target_date', type: 'string', format: 'date', description: 'Target date'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Project created successfully',
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
            'name'        => ['required', 'string', 'max:150'],
            'key'         => ['sometimes', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'team_id'     => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'lead_id'     => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'start_date'  => ['sometimes', 'nullable', 'date'],
            'target_date' => ['sometimes', 'nullable', 'date', 'after:start_date'],
        ]);

        $workspace = Workspace::findOrFail($this->currentWorkspaceId());
        $project   = $this->service->create($workspace, $data, $request->user());

        return $this->created($project);
    }

    #[OA\Get(
        path: '/projects/{project}',
        operationId: 'getProject',
        summary: 'Get project details',
        description: 'Returns details of a specific project.',
        tags: ['Projects']
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
        description: 'Project retrieved successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function show(Project $project): JsonResponse
    {
        return $this->ok($this->service->find($project->id));
    }

    #[OA\Put(
        path: '/projects/{project}',
        operationId: 'updateProject',
        summary: 'Update a project',
        description: 'Updates an existing project.',
        tags: ['Projects']
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
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'The name of the project'),
                new OA\Property(property: 'description', type: 'string', description: 'The description of the project'),
                new OA\Property(property: 'color', type: 'string', description: 'Color for the project'),
                new OA\Property(property: 'health', type: 'string', enum: ['on-track', 'at-risk', 'off-track'], description: 'Health status'),
                new OA\Property(property: 'progress', type: 'integer', description: 'Progress percentage (0-100)'),
                new OA\Property(property: 'team_id', type: 'integer', description: 'ID of the team'),
                new OA\Property(property: 'lead_id', type: 'integer', description: 'ID of the project lead'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', description: 'Start date'),
                new OA\Property(property: 'target_date', type: 'string', format: 'date', description: 'Target date'),
                new OA\Property(property: 'is_archived', type: 'boolean', description: 'Whether the project is archived'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Project updated successfully',
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
    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'health'      => ['sometimes', 'in:on-track,at-risk,off-track'],
            'progress'    => ['sometimes', 'integer', 'min:0', 'max:100'],
            'team_id'     => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'lead_id'     => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'start_date'  => ['sometimes', 'nullable', 'date'],
            'target_date' => ['sometimes', 'nullable', 'date'],
            'is_archived' => ['sometimes', 'boolean'],
        ]);

        return $this->ok($this->service->update($project, $data));
    }

    #[OA\Delete(
        path: '/projects/{project}',
        operationId: 'deleteProject',
        summary: 'Delete a project',
        description: 'Deletes an existing project.',
        tags: ['Projects']
    )]
    #[OA\Parameter(
        name: 'project',
        in: 'path',
        required: true,
        description: 'The ID of the project',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Project deleted successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function destroy(Project $project): JsonResponse
    {
        $this->service->delete($project);

        return $this->noContent();
    }

    #[OA\Post(
        path: '/projects/{project}/favorite',
        operationId: 'toggleFavorite',
        summary: 'Toggle project favorite status',
        description: 'Toggles the favorite status of a project.',
        tags: ['Projects']
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
        description: 'Favorite status toggled',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_favorite', type: 'boolean'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function toggleFavorite(Request $request, Project $project): JsonResponse
    {
        $isFavorite = $this->service->toggleFavorite($project, $request->user());

        return $this->ok(['is_favorite' => $isFavorite]);
    }

    #[OA\Get(
        path: '/projects/{project}/members',
        operationId: 'listProjectMembers',
        summary: 'List project members',
        description: 'Returns a list of members in the project.',
        tags: ['Projects']
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
        description: 'Members retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function members(Project $project): JsonResponse
    {
        return $this->ok($this->service->members($project));
    }

    #[OA\Post(
        path: '/projects/{project}/members',
        operationId: 'addProjectMember',
        summary: 'Add member to project',
        description: 'Adds a member to the project.',
        tags: ['Projects']
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
            required: ['user_id'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', description: 'ID of the user to add'),
                new OA\Property(property: 'role', type: 'string', enum: ['admin', 'member', 'viewer'], description: 'Role in the project'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Member added to project'
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
    public function addMember(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['sometimes', 'in:admin,member,viewer'],
        ]);

        $this->service->addMember($project, $data['user_id'], $data['role'] ?? 'member');

        return $this->ok(null, 'Member added');
    }

    #[OA\Delete(
        path: '/projects/{project}/members/{user}',
        operationId: 'removeProjectMember',
        summary: 'Remove member from project',
        description: 'Removes a member from the project.',
        tags: ['Projects']
    )]
    #[OA\Parameter(
        name: 'project',
        in: 'path',
        required: true,
        description: 'The ID of the project',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'The ID of the user to remove',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 204,
        description: 'Member removed from project'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function removeMember(Project $project, int $user): JsonResponse
    {
        $this->service->removeMember($project, $user);

        return $this->noContent();
    }
}
