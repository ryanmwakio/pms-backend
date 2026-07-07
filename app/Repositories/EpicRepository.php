<?php

namespace App\Repositories;

use App\Models\Epic;
use App\Repositories\Contracts\EpicRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EpicRepository implements EpicRepositoryInterface
{
    public function allForProject(int $projectId): Collection
    {
        return Epic::where('project_id', $projectId)
            ->withCount('issues')
            ->with('assignee')
            ->orderBy('position')
            ->get();
    }

    public function findById(int $id): Epic
    {
        return Epic::with(['project', 'assignee', 'issues.status'])->findOrFail($id);
    }

    public function create(array $data): Epic
    {
        return Epic::create($data);
    }

    public function update(Epic $epic, array $data): Epic
    {
        $epic->update($data);

        return $epic->fresh();
    }

    public function delete(Epic $epic): void
    {
        // Detach issues from epic before delete
        $epic->issues()->update(['epic_id' => null]);
        $epic->delete();
    }
}
