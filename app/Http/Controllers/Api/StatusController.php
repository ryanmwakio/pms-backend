<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends BaseController
{
    public function index(Project $project): JsonResponse
    {
        return $this->ok($project->statuses()->orderBy('position')->get());
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:60'],
            'color'    => ['sometimes', 'string', 'max:20'],
            'icon'     => ['sometimes', 'string', 'max:10'],
            'category' => ['sometimes', 'in:todo,in_progress,done'],
        ]);

        $data['project_id'] = $project->id;
        $data['position']   = $project->statuses()->max('position') + 1;

        return $this->created(Status::create($data));
    }

    public function show(Status $status): JsonResponse
    {
        return $this->ok($status);
    }

    public function update(Request $request, Status $status): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:60'],
            'color'      => ['sometimes', 'string', 'max:20'],
            'icon'       => ['sometimes', 'string', 'max:10'],
            'category'   => ['sometimes', 'in:todo,in_progress,done'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['is_default'])) {
            // Unset previous default for this project
            Status::where('project_id', $status->project_id)
                ->where('id', '!=', $status->id)
                ->update(['is_default' => false]);
        }

        $status->update($data);

        return $this->ok($status->fresh());
    }

    public function destroy(Status $status): JsonResponse
    {
        // Prevent deleting a status that still has issues
        if ($status->issues()->exists()) {
            return $this->error('Cannot delete a status that still has issues.', 422);
        }

        $status->delete();

        return $this->noContent();
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:statuses,id'],
        ]);

        foreach ($data['order'] as $position => $id) {
            Status::where('id', $id)
                ->where('project_id', $project->id)
                ->update(['position' => $position]);
        }

        return $this->ok($project->statuses()->orderBy('position')->get());
    }
}
