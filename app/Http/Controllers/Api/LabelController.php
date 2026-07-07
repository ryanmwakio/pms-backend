<?php

namespace App\Http\Controllers\Api;

use App\Models\Label;
use App\Models\Workspace;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends BaseController
{
    public function __construct(private readonly LabelService $service) {}

    public function index(): JsonResponse
    {
        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->ok($this->service->list($workspace));
    }

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

    public function show(Label $label): JsonResponse
    {
        return $this->ok($label);
    }

    public function update(Request $request, Label $label): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:60'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'description' => ['sometimes', 'nullable', 'string', 'max:200'],
        ]);

        return $this->ok($this->service->update($label, $data));
    }

    public function destroy(Label $label): JsonResponse
    {
        $this->service->delete($label);

        return $this->noContent();
    }
}
