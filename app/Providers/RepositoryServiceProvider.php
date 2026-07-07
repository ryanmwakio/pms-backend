<?php

namespace App\Providers;

use App\Repositories\Contracts\EpicRepositoryInterface;
use App\Repositories\Contracts\IssueRepositoryInterface;
use App\Repositories\Contracts\LabelRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\SprintRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Repositories\EpicRepository;
use App\Repositories\IssueRepository;
use App\Repositories\LabelRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SprintRepository;
use App\Repositories\TeamRepository;
use App\Repositories\WorkspaceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkspaceRepositoryInterface::class, WorkspaceRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(IssueRepositoryInterface::class, IssueRepository::class);
        $this->app->bind(SprintRepositoryInterface::class, SprintRepository::class);
        $this->app->bind(EpicRepositoryInterface::class, EpicRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(LabelRepositoryInterface::class, LabelRepository::class);
    }
}
