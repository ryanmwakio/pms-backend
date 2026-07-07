<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    public function __construct(private readonly ReportService $service) {}

    public function overview(Request $request, Project $project): JsonResponse
    {
        $period = $request->input('period', '30d');

        return $this->ok($this->service->overview($project, $period));
    }

    public function burndown(Request $request, Project $project): JsonResponse
    {
        $sprintId = $request->input('sprint_id');

        return $this->ok($this->service->burndown($project, $sprintId));
    }

    public function velocity(Project $project): JsonResponse
    {
        return $this->ok($this->service->velocity($project));
    }

    public function cycleTime(Request $request, Project $project): JsonResponse
    {
        $period = $request->input('period', '30d');

        return $this->ok($this->service->cycleTime($project, $period));
    }

    public function teamPerformance(Request $request, Project $project): JsonResponse
    {
        $period = $request->input('period', '30d');

        return $this->ok($this->service->teamPerformance($project, $period));
    }
}
