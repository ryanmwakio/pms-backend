<?php

namespace App\Http\Controllers\Api;

use App\Models\Team;
use App\Models\Workspace;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends BaseController
{
    public function __construct(private readonly TeamService $service) {}

    public function index(): JsonResponse
    {
        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->ok($this->service->list($workspace));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'lead_id'     => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->created($this->service->create($workspace, $data));
    }

    public function show(Team $team): JsonResponse
    {
        return $this->ok($this->service->find($team->id));
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'color'       => ['sometimes', 'string', 'max:20'],
            'lead_id'     => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        return $this->ok($this->service->update($team, $data));
    }

    public function destroy(Team $team): JsonResponse
    {
        $this->service->delete($team);

        return $this->noContent();
    }

    public function addMember(Request $request, Team $team): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['sometimes', 'in:lead,member'],
        ]);

        $this->service->addMember($team, $data['user_id'], $data['role'] ?? 'member');

        return $this->ok(null, 'Member added');
    }

    public function removeMember(Team $team, int $user): JsonResponse
    {
        $this->service->removeMember($team, $user);

        return $this->noContent();
    }
}
