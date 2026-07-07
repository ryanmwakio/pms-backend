<?php

namespace App\Repositories;

use App\Models\Issue;
use App\Models\Sprint;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SprintRepository implements SprintRepositoryInterface
{
    public function allForProject(int $projectId): Collection
    {
        return Sprint::where('project_id', $projectId)
            ->withCount('issues')
            ->with(['issues' => fn ($q) => $q->with('status')])
            ->orderBy('start_date')
            ->get();
    }

    public function findById(int $id): Sprint
    {
        return Sprint::with([
            'issues.status',
            'issues.assignee',
            'issues.labels',
            'issues.epic',
        ])->findOrFail($id);
    }

    public function create(array $data): Sprint
    {
        return Sprint::create($data);
    }

    public function update(Sprint $sprint, array $data): Sprint
    {
        $sprint->update($data);

        return $sprint->fresh();
    }

    public function delete(Sprint $sprint): void
    {
        // Move all sprint issues back to backlog
        $sprint->issues()->update(['sprint_id' => null]);
        $sprint->delete();
    }

    public function start(Sprint $sprint): Sprint
    {
        // Only one active sprint per project
        Sprint::where('project_id', $sprint->project_id)
            ->where('status', 'active')
            ->update(['status' => 'planned']);

        $sprint->update([
            'status'     => 'active',
            'started_at' => now(),
        ]);

        return $sprint->fresh();
    }

    public function complete(Sprint $sprint, ?int $carryOverSprintId = null): Sprint
    {
        DB::transaction(function () use ($sprint, $carryOverSprintId) {
            // Carry over incomplete issues
            $incomplete = $sprint->issues()
                ->whereHas('status', fn ($q) => $q->where('category', '!=', 'done'))
                ->pluck('id');

            if ($incomplete->isNotEmpty()) {
                Issue::whereIn('id', $incomplete)->update([
                    'sprint_id' => $carryOverSprintId,
                ]);
            }

            $velocity = $sprint->completedPoints();

            $sprint->update([
                'status'       => 'completed',
                'completed_at' => now(),
                'velocity'     => $velocity,
            ]);
        });

        return $sprint->fresh();
    }

    public function addIssue(Sprint $sprint, int $issueId): void
    {
        Issue::where('id', $issueId)
            ->where('project_id', $sprint->project_id)
            ->update(['sprint_id' => $sprint->id]);
    }

    public function removeIssue(Sprint $sprint, int $issueId): void
    {
        Issue::where('id', $issueId)
            ->where('sprint_id', $sprint->id)
            ->update(['sprint_id' => null]);
    }

    public function activeForProject(int $projectId): ?Sprint
    {
        return Sprint::where('project_id', $projectId)
            ->where('status', 'active')
            ->first();
    }
}
