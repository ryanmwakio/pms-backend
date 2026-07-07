<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;

class DashboardService
{
    public function getData(Project $project): array
    {
        $activeSprint = $project->activeSprint;
        $sprintIssues = $activeSprint
            ? Issue::where('sprint_id', $activeSprint->id)
                ->with('status')
                ->get()
            : collect();

        $projectIssues = Issue::where('project_id', $project->id)->with('status')->get();

        return [
            'project'          => $project->load(['lead', 'team', 'activeSprint']),
            'sprint_progress'  => $this->sprintProgress($sprintIssues),
            'status_breakdown' => $this->statusBreakdown($projectIssues, $project),
            'priority_breakdown' => $this->priorityBreakdown($projectIssues),
            'team_workload'    => $this->teamWorkload($project),
            'recent_activity'  => $project->activities()
                ->with('user')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            'upcoming_deadlines' => Issue::where('project_id', $project->id)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '>=', today())
                ->whereDate('due_date', '<=', today()->addDays(14))
                ->with(['status', 'assignee'])
                ->orderBy('due_date')
                ->get(),
            'milestones'       => $project->sprints()
                ->orderBy('start_date')
                ->get(),
        ];
    }

    private function sprintProgress(\Illuminate\Support\Collection $issues): array
    {
        if ($issues->isEmpty()) {
            return ['pct' => 0, 'done' => 0, 'total' => 0, 'points_done' => 0, 'points_total' => 0];
        }

        $done  = $issues->filter(fn ($i) => $i->status?->category === 'done');
        $total = $issues->count();
        $donePoints  = $done->sum('story_points');
        $totalPoints = $issues->sum('story_points');

        return [
            'pct'          => $total ? round($done->count() / $total * 100) : 0,
            'done'         => $done->count(),
            'total'        => $total,
            'points_done'  => $donePoints,
            'points_total' => $totalPoints,
        ];
    }

    private function statusBreakdown(\Illuminate\Support\Collection $issues, Project $project): array
    {
        $statuses = $project->statuses;
        $total    = $issues->count();

        return $statuses->map(fn ($s) => [
            'id'    => $s->id,
            'name'  => $s->name,
            'color' => $s->color,
            'icon'  => $s->icon,
            'count' => $issues->where('status_id', $s->id)->count(),
            'pct'   => $total ? round($issues->where('status_id', $s->id)->count() / $total * 100) : 0,
        ])->values()->toArray();
    }

    private function priorityBreakdown(\Illuminate\Support\Collection $issues): array
    {
        $priorities = ['urgent', 'high', 'medium', 'low', 'none'];
        $total      = $issues->count();

        return collect($priorities)->map(fn ($p) => [
            'priority' => $p,
            'count'    => $issues->where('priority', $p)->count(),
            'pct'      => $total ? round($issues->where('priority', $p)->count() / $total * 100) : 0,
        ])->values()->toArray();
    }

    private function teamWorkload(Project $project): array
    {
        return $project->members()
            ->get()
            ->map(function ($user) use ($project) {
                $active = Issue::where('project_id', $project->id)
                    ->where('assignee_id', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('category', '!=', 'done'))
                    ->count();

                $done = Issue::where('project_id', $project->id)
                    ->where('assignee_id', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('category', 'done'))
                    ->count();

                return [
                    'user'   => $user,
                    'active' => $active,
                    'done'   => $done,
                    'total'  => $active + $done,
                ];
            })
            ->toArray();
    }
}
