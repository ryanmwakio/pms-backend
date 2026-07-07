<?php

namespace App\Http\Controllers\Api;

use App\Models\Epic;
use App\Models\Project;
use App\Services\EpicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpicController extends BaseController
{
    public function __construct(private readonly EpicService $service) {}

    public function index(Project $project): JsonResponse
    {
        return $this->ok($this->service->list($project));
    }

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

    public function show(Epic $epic): JsonResponse
    {
        return $this->ok($this->service->find($epic->id));
    }

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

    public function destroy(Epic $epic): JsonResponse
    {
        $this->service->delete($epic);

        return $this->noContent();
    }
}
