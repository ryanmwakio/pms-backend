<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends BaseController
{
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
