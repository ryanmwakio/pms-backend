<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    public function __construct(private readonly DashboardService $service) {}

    public function show(Project $project): JsonResponse
    {
        return $this->ok($this->service->getData($project));
    }
}
