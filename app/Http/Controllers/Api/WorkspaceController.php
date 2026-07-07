<?php

namespace App\Http\Controllers\Api;

use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends BaseController
{
    public function __construct(private readonly WorkspaceService $service) {}

    public function index(Request $request): JsonResponse
    {
        return $this->ok($this->service->list($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['sometimes', 'string', 'max:60', 'unique:workspaces'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'color'       => ['sometimes', 'string', 'max:20'],
        ]);

        $workspace = $this->service->create($data, $request->user());

        return $this->created($workspace);
    }

    public function show(Workspace $workspace): JsonResponse
    {
        return $this->ok($workspace->load(['owner', 'members', 'teams']));
    }

    public function update(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'color'       => ['sometimes', 'string', 'max:20'],
        ]);

        return $this->ok($this->service->update($workspace, $data));
    }

    public function destroy(Workspace $workspace): JsonResponse
    {
        $this->authorize('delete', $workspace);
        $this->service->delete($workspace);

        return $this->noContent();
    }

    public function addMember(Request $request, Workspace $workspace): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['sometimes', 'in:admin,member,viewer'],
        ]);

        $this->service->addMember($workspace, $data['user_id'], $data['role'] ?? 'member');

        return $this->ok(null, 'Member added');
    }

    public function removeMember(Workspace $workspace, int $user): JsonResponse
    {
        $this->service->removeMember($workspace, $user);

        return $this->noContent();
    }
}
