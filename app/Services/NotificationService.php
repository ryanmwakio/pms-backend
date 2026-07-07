<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\PmsNotification;
use App\Models\User;

class NotificationService
{
    public function notifyMention(Issue $issue, User $actor, User $mentioned): void
    {
        $this->create(
            user: $mentioned,
            actor: $actor,
            issue: $issue,
            type: 'mention',
            title: "{$actor->name} mentioned you in {$issue->key}",
            body: $issue->title,
        );
    }

    public function notifyAssigned(Issue $issue, User $actor, User $assignee): void
    {
        if ($actor->id === $assignee->id) {
            return;
        }

        $this->create(
            user: $assignee,
            actor: $actor,
            issue: $issue,
            type: 'assign',
            title: "{$actor->name} assigned {$issue->key} to you",
            body: $issue->title,
        );
    }

    public function notifyComment(Issue $issue, User $actor): void
    {
        // Notify all watchers except the commenter
        foreach ($issue->watchers as $watcher) {
            if ($watcher->id === $actor->id) {
                continue;
            }

            $this->create(
                user: $watcher,
                actor: $actor,
                issue: $issue,
                type: 'comment',
                title: "{$actor->name} commented on {$issue->key}",
                body: $issue->title,
            );
        }
    }

    public function notifyStatusChange(Issue $issue, User $actor, string $newStatus): void
    {
        foreach ($issue->watchers as $watcher) {
            if ($watcher->id === $actor->id) {
                continue;
            }

            $this->create(
                user: $watcher,
                actor: $actor,
                issue: $issue,
                type: 'status_change',
                title: "{$issue->key} moved to {$newStatus}",
                body: $issue->title,
            );
        }
    }

    public function getForUser(User $user, bool $unreadOnly = false): \Illuminate\Database\Eloquent\Collection
    {
        return PmsNotification::where('user_id', $user->id)
            ->when($unreadOnly, fn ($q) => $q->where('is_read', false))
            ->with(['actor', 'issue', 'project'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function markRead(PmsNotification $notification): void
    {
        $notification->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAllRead(User $user): void
    {
        PmsNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function unreadCount(User $user): int
    {
        return PmsNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    private function create(
        User $user,
        ?User $actor,
        ?Issue $issue,
        string $type,
        string $title,
        ?string $body = null
    ): PmsNotification {
        return PmsNotification::create([
            'user_id'    => $user->id,
            'actor_id'   => $actor?->id,
            'issue_id'   => $issue?->id,
            'project_id' => $issue?->project_id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
        ]);
    }
}
