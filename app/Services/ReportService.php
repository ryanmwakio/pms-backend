<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Support\Carbon;

class ReportService
{
    public function overview(Project $project, string $period = '30d'): array
    {
        $since = $this->periodToDate($period);

        $issues     = Issue::where('project_id', $project->id)->get();
        $created    = Issue::where('project_id', $project->id)->where('created_at', '>=', $since)->count();
        $closed     = Issue::where('project_id', $project->id)
            ->whereHas('status', fn ($q) => $q->where('category', 'done'))
            ->where('completed_at', '>=', $since)
            ->count();

        $cycleTime  = $this->avgCycleTime($project, $since);
        $leadTime   = $this->avgLeadTime($project, $since);

        return [
            'issues_created'    => $created,
            'issues_closed'     => $closed,
            'completion_rate'   => $created > 0 ? round($closed / $created * 100) : 0,
            'avg_cycle_time'    => $cycleTime,
            'avg_lead_time'     => $leadTime,
            'total_open'        => $issues->filter(fn ($i) => $i->status?->category !== 'done')->count(),
            'total_closed'      => $issues->filter(fn ($i) => $i->status?->category === 'done')->count(),
            'by_type'           => $this->countByField($issues, 'type'),
            'by_priority'       => $this->countByField($issues, 'priority'),
        ];
    }

    public function burndown(Project $project, ?int $sprintId = null): array
    {
        $sprint = $sprintId
            ? Sprint::findOrFail($sprintId)
            : $project->activeSprint;

        if (! $sprint || ! $sprint->start_date || ! $sprint->end_date) {
            return ['sprint' => null, 'data' => []];
        }

        $issues     = $sprint->issues()->with('status')->get();
        $totalPoints = $issues->sum('story_points');
        $startDate  = $sprint->start_date;
        $endDate    = min($sprint->end_date, today());
        $days       = [];
        $current    = $startDate->copy();
        $dayCount   = $startDate->diffInDays($sprint->end_date);

        while ($current <= $endDate) {
            $completedByDay = $issues
                ->filter(fn ($i) => $i->status?->category === 'done' && $i->completed_at && $i->completed_at->lte($current))
                ->sum('story_points');

            $dayNum = $startDate->diffInDays($current) + 1;
            $idealRemaining = $dayCount > 0
                ? round($totalPoints - ($totalPoints * $dayNum / $dayCount))
                : 0;

            $days[] = [
                'day'     => $current->format('M d'),
                'ideal'   => max(0, $idealRemaining),
                'actual'  => max(0, $totalPoints - $completedByDay),
            ];

            $current->addDay();
        }

        return [
            'sprint'       => $sprint,
            'total_points' => $totalPoints,
            'data'         => $days,
        ];
    }

    public function velocity(Project $project): array
    {
        $sprints = Sprint::where('project_id', $project->id)
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        return $sprints->map(fn ($s) => [
            'sprint'    => $s->name,
            'committed' => $s->totalPoints(),
            'completed' => $s->completedPoints(),
            'velocity'  => $s->velocity,
        ])->toArray();
    }

    public function cycleTime(Project $project, string $period = '30d'): array
    {
        $since = $this->periodToDate($period);

        $closed = Issue::where('project_id', $project->id)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->get();

        return [
            'avg_days'   => $this->avgCycleTime($project, $since),
            'by_type'    => $closed->groupBy('type')
                ->map(fn ($group, $type) => [
                    'type'     => $type,
                    'avg_days' => round($group->avg(fn ($i) => $i->started_at->diffInDays($i->completed_at)), 1),
                    'count'    => $group->count(),
                ])->values(),
            'data'       => $closed->map(fn ($i) => [
                'key'      => $i->key,
                'title'    => $i->title,
                'type'     => $i->type,
                'days'     => round($i->started_at->diffInDays($i->completed_at), 1),
                'closed'   => $i->completed_at?->toDateString(),
            ])->values(),
        ];
    }

    public function teamPerformance(Project $project, string $period = '30d'): array
    {
        $since = $this->periodToDate($period);

        return $project->members()->get()->map(function ($user) use ($project, $since) {
            $closed = Issue::where('project_id', $project->id)
                ->where('assignee_id', $user->id)
                ->whereHas('status', fn ($q) => $q->where('category', 'done'))
                ->where('completed_at', '>=', $since)
                ->get();

            $active = Issue::where('project_id', $project->id)
                ->where('assignee_id', $user->id)
                ->whereHas('status', fn ($q) => $q->where('category', '!=', 'done'))
                ->count();

            return [
                'user'          => $user,
                'closed_count'  => $closed->count(),
                'closed_points' => $closed->sum('story_points'),
                'active_count'  => $active,
            ];
        })->toArray();
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    private function periodToDate(string $period): Carbon
    {
        return match ($period) {
            '7d'    => now()->subDays(7),
            '30d'   => now()->subDays(30),
            '90d'   => now()->subDays(90),
            'all'   => now()->subYears(10),
            default => now()->subDays(30),
        };
    }

    private function avgCycleTime(Project $project, Carbon $since): float
    {
        $avg = Issue::where('project_id', $project->id)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)) / 24 as avg_days')
            ->value('avg_days');

        return round((float) $avg, 1);
    }

    private function avgLeadTime(Project $project, Carbon $since): float
    {
        $avg = Issue::where('project_id', $project->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) / 24 as avg_days')
            ->value('avg_days');

        return round((float) $avg, 1);
    }

    private function countByField(\Illuminate\Database\Eloquent\Collection $issues, string $field): array
    {
        return $issues->groupBy($field)
            ->map(fn ($group, $key) => ['value' => $key, 'count' => $group->count()])
            ->values()
            ->toArray();
    }
}
