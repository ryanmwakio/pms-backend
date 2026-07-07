<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use App\Repositories\Contracts\SprintRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SprintService
{
    public function __construct(
        private readonly SprintRepositoryInterface $repo,
        private readonly ActivityService $activity,
    ) {}

    public function list(Project $project): Collection
    {
        return $this->repo->allForProject($project->id);
    }

    public function find(int $id): Sprint
    {
        return $this->repo->findById($id);
    }

    public function create(Project $project, array $data): Sprint
    {
        $data['project_id'] = $project->id;

        return $this->repo->create($data);
    }

    public function update(Sprint $sprint, array $data): Sprint
    {
        return $this->repo->update($sprint, $data);
    }

    public function delete(Sprint $sprint): void
    {
        if ($sprint->status === 'active') {
            throw ValidationException::withMessages([
                'sprint' => ['Cannot delete an active sprint. Complete it first.'],
            ]);
        }

        $this->repo->delete($sprint);
    }

    public function start(Sprint $sprint, User $actor): Sprint
    {
        if ($sprint->status !== 'planned') {
            throw ValidationException::withMessages([
                'sprint' => ['Only planned sprints can be started.'],
            ]);
        }

        $started = $this->repo->start($sprint);

        $this->activity->log('sprint_started', $actor, project: $sprint->project, meta: [
            'sprint_id'   => $sprint->id,
            'sprint_name' => $sprint->name,
        ]);

        return $started;
    }

    public function complete(Sprint $sprint, User $actor, ?int $carryOverSprintId = null): Sprint
    {
        if ($sprint->status !== 'active') {
            throw ValidationException::withMessages([
                'sprint' => ['Only active sprints can be completed.'],
            ]);
        }

        $completed = $this->repo->complete($sprint, $carryOverSprintId);

        $this->activity->log('sprint_completed', $actor, project: $sprint->project, meta: [
            'sprint_id'   => $sprint->id,
            'sprint_name' => $sprint->name,
            'velocity'    => $completed->velocity,
        ]);

        return $completed;
    }

    public function addIssue(Sprint $sprint, int $issueId): void
    {
        $this->repo->addIssue($sprint, $issueId);
    }

    public function removeIssue(Sprint $sprint, int $issueId): void
    {
        $this->repo->removeIssue($sprint, $issueId);
    }
}
