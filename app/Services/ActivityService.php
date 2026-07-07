<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;

class ActivityService
{
    public function log(
        string $action,
        ?User $user = null,
        ?Issue $issue = null,
        ?Project $project = null,
        array $meta = []
    ): Activity {
        return Activity::create([
            'user_id'    => $user?->id,
            'project_id' => $project?->id ?? $issue?->project_id,
            'issue_id'   => $issue?->id,
            'action'     => $action,
            'meta'       => $meta,
        ]);
    }

    public function logIssueCreated(Issue $issue, User $user): void
    {
        $this->log('created', $user, $issue, meta: [
            'title' => $issue->title,
            'key'   => $issue->key,
        ]);
    }

    public function logStatusChanged(Issue $issue, User $user, string $from, string $to): void
    {
        $this->log('status_changed', $user, $issue, meta: [
            'from' => $from,
            'to'   => $to,
        ]);
    }

    public function logAssigned(Issue $issue, User $actor, ?User $assignee): void
    {
        $this->log('assigned', $actor, $issue, meta: [
            'assignee_id'   => $assignee?->id,
            'assignee_name' => $assignee?->name,
        ]);
    }

    public function logPriorityChanged(Issue $issue, User $user, string $from, string $to): void
    {
        $this->log('priority_changed', $user, $issue, meta: [
            'from' => $from,
            'to'   => $to,
        ]);
    }

    public function logCommented(Issue $issue, User $user): void
    {
        $this->log('commented', $user, $issue);
    }

    public function logSprintChanged(Issue $issue, User $user, ?string $from, ?string $to): void
    {
        $this->log('sprint_changed', $user, $issue, meta: [
            'from' => $from,
            'to'   => $to,
        ]);
    }
}
