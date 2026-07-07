<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Workspace;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends BaseController
{
    public function __construct(private readonly ProjectService $service) {}

    public function index(Request $request): JsonResponse
    {
        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        return $this->ok($this->service->list($workspace, $request->only('is_archived', 'health')));
    }

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

    public function show(Project $project): JsonResponse
    {
        return $this->ok($this->service->find($project->id));
    }

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

    public function destroy(Project $project): JsonResponse
    {
        $this->service->delete($project);

        return $this->noContent();
    }

    public function toggleFavorite(Request $request, Project $project): JsonResponse
    {
        $isFavorite = $this->service->toggleFavorite($project, $request->user());

        return $this->ok(['is_favorite' => $isFavorite]);
    }

    public function members(Project $project): JsonResponse
    {
        return $this->ok($this->service->members($project));
    }

    public function addMember(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['sometimes', 'in:admin,member,viewer'],
        ]);

        $this->service->addMember($project, $data['user_id'], $data['role'] ?? 'member');

        return $this->ok(null, 'Member added');
    }

    public function removeMember(Project $project, int $user): JsonResponse
    {
        $this->service->removeMember($project, $user);

        return $this->noContent();
    }
}
