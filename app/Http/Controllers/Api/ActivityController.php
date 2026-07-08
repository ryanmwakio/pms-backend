<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ActivityController extends BaseController
{
        
    #[OA\Get(
        path: '/activity',
        operationId: 'getActivities',
        summary: 'Get recent activities',
        description: 'Returns the latest 50 activities for the current workspace.',
        tags: ['Activities']
    )]
    #[OA\Response(
        response: 200,
        description: 'Activities retrieved successfully'
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
        $workspaceId = $this->currentWorkspaceId();

        $activities = Activity::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
            ->with(['user', 'issue', 'project'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $this->ok($activities);
    }

    #[OA\Get(
        path: '/projects/{project}/activity',
        operationId: 'getProjectActivity',
        summary: 'Get project activity',
        description: 'Returns the latest 50 activities for the specified project.',
        tags: ['Activities']
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
        description: 'Activities retrieved successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden'
    )]
    public function projectActivity(Request $request, Project $project): JsonResponse
    {
        $activities = $project->activities()
            ->with(['user', 'issue'])
            ->orderByDesc('created_at')
            ->limit($request->input('limit', 50))
            ->get();

        return $this->ok($activities);
    }
}
