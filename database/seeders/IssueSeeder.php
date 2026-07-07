<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    public function run(): void
    {
        $pms = Project::where('key', 'PMS')->first();
        $int = Project::where('key', 'INT')->first();

        $statuses  = Status::whereIn('project_id', [$pms->id, $int->id])->get()->groupBy('project_id');
        $sprints   = Sprint::whereIn('project_id', [$pms->id, $int->id])->get()->keyBy('name');
        $epics     = Epic::whereIn('project_id', [$pms->id, $int->id])->get()->keyBy('title');
        $users     = User::all()->keyBy('email');
        $workspace = Workspace::where('slug', 'acme')->first();
        $labels    = Label::where('workspace_id', $workspace->id)->get()->keyBy('name');

        $statusId = fn ($project, $name) => $statuses[$project->id]->firstWhere('name', $name)?->id
            ?? $statuses[$project->id]->first()->id;
        $sprintId = fn ($name) => $name ? $sprints[$name]?->id : null;
        $epicId   = fn ($title) => $title ? $epics[$title]?->id : null;
        $userId   = fn ($email) => $email ? $users[$email]?->id : null;

        foreach ($this->definitions($pms, $int, $statusId, $sprintId, $epicId, $userId) as $def) {
            $project    = $def['_project'];
            $labelNames = $def['_labels'];
            unset($def['_project'], $def['_labels']);

            $def['project_id'] = $project->id;

            $issue = Issue::firstOrCreate(
                ['project_id' => $project->id, 'key' => $def['key']],
                $def
            );

            $labelIds = $labels->only($labelNames)->pluck('id');
            $issue->labels()->syncWithoutDetaching($labelIds);

            foreach (array_filter([$def['assignee_id'] ?? null, $def['reporter_id'] ?? null]) as $uid) {
                $issue->watchers()->syncWithoutDetaching([$uid]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(
        Project $pms, Project $int,
        callable $statusId, callable $sprintId,
        callable $epicId, callable $userId
    ): array {
        return [
            [
                '_project'     => $pms,
                '_labels'      => ['feature'],
                'key'          => 'PMS-1',
                'title'        => 'Set up authentication with OAuth2',
                'description'  => 'Implement OAuth2 flow with Google and GitHub providers. Include refresh token handling and secure cookie storage.',
                'type'         => 'story',
                'status_id'    => $statusId($pms, 'Done'),
                'priority'     => 'high',
                'assignee_id'  => $userId('alex@acme.io'),
                'reporter_id'  => $userId('alex@acme.io'),
                'epic_id'      => $epicId('Authentication & Onboarding'),
                'sprint_id'    => $sprintId('Sprint 1'),
                'story_points' => 5,
                'created_at'   => '2026-06-01 09:00:00',
                'updated_at'   => '2026-06-01 09:00:00',
                'due_date'     => '2026-06-15',
                'completed_at' => '2026-06-13 16:00:00',
                'position'     => 0,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['feature', 'design'],
                'key'          => 'PMS-2',
                'title'        => 'Build user onboarding wizard',
                'description'  => 'Multi-step onboarding flow: workspace creation, invite team, configure project defaults.',
                'type'         => 'story',
                'status_id'    => $statusId($pms, 'Done'),
                'priority'     => 'high',
                'assignee_id'  => $userId('sam@acme.io'),
                'reporter_id'  => $userId('alex@acme.io'),
                'epic_id'      => $epicId('Authentication & Onboarding'),
                'sprint_id'    => $sprintId('Sprint 1'),
                'story_points' => 8,
                'created_at'   => '2026-06-02 09:00:00',
                'updated_at'   => '2026-06-02 09:00:00',
                'due_date'     => '2026-06-20',
                'completed_at' => '2026-06-14 10:00:00',
                'position'     => 1,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['bug'],
                'key'          => 'PMS-3',
                'title'        => 'Fix login redirect loop on token expiry',
                'description'  => 'When access token expires mid-session, users are caught in a redirect loop instead of being shown the login screen.',
                'type'         => 'bug',
                'status_id'    => $statusId($pms, 'In Progress'),
                'priority'     => 'urgent',
                'assignee_id'  => $userId('jordan@acme.io'),
                'reporter_id'  => $userId('casey@acme.io'),
                'epic_id'      => $epicId('Authentication & Onboarding'),
                'sprint_id'    => $sprintId('Sprint 2'),
                'story_points' => 2,
                'created_at'   => '2026-06-10 09:00:00',
                'updated_at'   => '2026-06-10 09:00:00',
                'due_date'     => '2026-07-08',
                'started_at'   => '2026-06-30 09:00:00',
                'position'     => 0,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['feature', 'design'],
                'key'          => 'PMS-4',
                'title'        => 'Dashboard analytics widgets',
                'description'  => 'Build configurable dashboard widgets: burndown chart, velocity, team workload, and sprint progress.',
                'type'         => 'story',
                'status_id'    => $statusId($pms, 'In Progress'),
                'priority'     => 'medium',
                'assignee_id'  => $userId('morgan@acme.io'),
                'reporter_id'  => $userId('sam@acme.io'),
                'epic_id'      => $epicId('Dashboard & Analytics'),
                'sprint_id'    => $sprintId('Sprint 2'),
                'story_points' => 13,
                'created_at'   => '2026-06-12 09:00:00',
                'updated_at'   => '2026-06-12 09:00:00',
                'due_date'     => '2026-07-20',
                'started_at'   => '2026-07-01 09:00:00',
                'position'     => 1,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['security', 'performance'],
                'key'          => 'PMS-5',
                'title'        => 'REST API rate limiting',
                'description'  => 'Implement token-bucket rate limiting per API key. Expose headers: X-RateLimit-Limit, X-RateLimit-Remaining.',
                'type'         => 'task',
                'status_id'    => $statusId($pms, 'In Review'),
                'priority'     => 'high',
                'assignee_id'  => $userId('riley@acme.io'),
                'reporter_id'  => $userId('alex@acme.io'),
                'epic_id'      => $epicId('API Infrastructure'),
                'sprint_id'    => $sprintId('Sprint 2'),
                'story_points' => 3,
                'created_at'   => '2026-06-14 09:00:00',
                'updated_at'   => '2026-06-14 09:00:00',
                'due_date'     => '2026-07-10',
                'started_at'   => '2026-07-02 14:00:00',
                'position'     => 0,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['feature', 'design'],
                'key'          => 'PMS-6',
                'title'        => 'Mobile navigation drawer',
                'description'  => 'Implement slide-out navigation drawer for mobile viewports. Support gesture-based dismiss.',
                'type'         => 'story',
                'status_id'    => $statusId($pms, 'To Do'),
                'priority'     => 'medium',
                'assignee_id'  => $userId('morgan@acme.io'),
                'reporter_id'  => $userId('sam@acme.io'),
                'epic_id'      => $epicId('Mobile Responsiveness'),
                'sprint_id'    => $sprintId('Sprint 2'),
                'story_points' => 5,
                'created_at'   => '2026-06-18 09:00:00',
                'updated_at'   => '2026-06-18 09:00:00',
                'due_date'     => '2026-07-25',
                'position'     => 0,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['bug', 'performance'],
                'key'          => 'PMS-7',
                'title'        => 'Slow query optimization on /api/issues',
                'description'  => 'The issues list endpoint takes 3-8s under load. Profile and add appropriate indexes.',
                'type'         => 'bug',
                'status_id'    => $statusId($pms, 'To Do'),
                'priority'     => 'high',
                'assignee_id'  => $userId('riley@acme.io'),
                'reporter_id'  => $userId('jordan@acme.io'),
                'epic_id'      => $epicId('API Infrastructure'),
                'sprint_id'    => $sprintId('Sprint 2'),
                'story_points' => 5,
                'created_at'   => '2026-06-20 09:00:00',
                'updated_at'   => '2026-06-20 09:00:00',
                'due_date'     => '2026-07-15',
                'position'     => 1,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['tech-debt'],
                'key'          => 'PMS-8',
                'title'        => 'Refactor auth middleware',
                'description'  => 'Extract auth logic from route handlers into dedicated middleware. Add unit tests.',
                'type'         => 'task',
                'status_id'    => $statusId($pms, 'To Do'),
                'priority'     => 'low',
                'assignee_id'  => $userId('jordan@acme.io'),
                'reporter_id'  => $userId('alex@acme.io'),
                'epic_id'      => $epicId('API Infrastructure'),
                'sprint_id'    => null,
                'story_points' => 3,
                'created_at'   => '2026-06-22 09:00:00',
                'updated_at'   => '2026-06-22 09:00:00',
                'position'     => 2,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['feature'],
                'key'          => 'PMS-9',
                'title'        => 'Export dashboard as PDF',
                'description'  => 'Allow users to export any dashboard view as a formatted PDF report.',
                'type'         => 'story',
                'status_id'    => $statusId($pms, 'To Do'),
                'priority'     => 'low',
                'assignee_id'  => null,
                'reporter_id'  => $userId('sam@acme.io'),
                'epic_id'      => $epicId('Dashboard & Analytics'),
                'sprint_id'    => null,
                'story_points' => 5,
                'created_at'   => '2026-06-25 09:00:00',
                'updated_at'   => '2026-06-25 09:00:00',
                'position'     => 3,
            ],
            [
                '_project'     => $int,
                '_labels'      => ['feature'],
                'key'          => 'INT-1',
                'title'        => 'Slack integration for notifications',
                'description'  => 'Connect workspace notifications to Slack channels. Support per-project channel routing.',
                'type'         => 'story',
                'status_id'    => $statusId($int, 'In Progress'),
                'priority'     => 'medium',
                'assignee_id'  => $userId('alex@acme.io'),
                'reporter_id'  => $userId('sam@acme.io'),
                'epic_id'      => $epicId('Integrations'),
                'sprint_id'    => null,
                'story_points' => 8,
                'created_at'   => '2026-06-28 09:00:00',
                'updated_at'   => '2026-06-28 09:00:00',
                'due_date'     => '2026-07-30',
                'started_at'   => '2026-07-01 10:00:00',
                'position'     => 0,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['feature', 'design'],
                'key'          => 'PMS-10',
                'title'        => 'Keyboard shortcut help modal',
                'description'  => 'Add a keyboard shortcut reference modal triggered by `?`.',
                'type'         => 'task',
                'status_id'    => $statusId($pms, 'In Review'),
                'priority'     => 'low',
                'assignee_id'  => $userId('morgan@acme.io'),
                'reporter_id'  => $userId('morgan@acme.io'),
                'epic_id'      => $epicId('Dashboard & Analytics'),
                'sprint_id'    => $sprintId('Sprint 2'),
                'story_points' => 2,
                'created_at'   => '2026-07-01 09:00:00',
                'updated_at'   => '2026-07-01 09:00:00',
                'due_date'     => '2026-07-12',
                'started_at'   => '2026-07-03 09:00:00',
                'position'     => 2,
            ],
            [
                '_project'     => $pms,
                '_labels'      => ['docs'],
                'key'          => 'PMS-11',
                'title'        => 'Update API documentation',
                'description'  => 'Audit and update OpenAPI spec to reflect recent endpoint changes.',
                'type'         => 'task',
                'status_id'    => $statusId($pms, 'To Do'),
                'priority'     => 'none',
                'assignee_id'  => $userId('sam@acme.io'),
                'reporter_id'  => $userId('alex@acme.io'),
                'epic_id'      => $epicId('API Infrastructure'),
                'sprint_id'    => null,
                'story_points' => 3,
                'created_at'   => '2026-07-02 09:00:00',
                'updated_at'   => '2026-07-02 09:00:00',
                'position'     => 4,
            ],
        ];
    }
}
