<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Issue;
use App\Models\User;

class CommentService
{
    public function __construct(
        private readonly ActivityService $activity,
        private readonly NotificationService $notifications,
    ) {}

    public function create(Issue $issue, array $data, User $author): Comment
    {
        $comment = Comment::create([
            'issue_id'  => $issue->id,
            'user_id'   => $author->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body'      => $data['body'],
        ]);

        $this->activity->logCommented($issue, $author);
        $this->notifications->notifyComment($issue, $author);

        // Auto-watch issue on comment
        $issue->watchers()->syncWithoutDetaching([$author->id]);

        return $comment->load(['user', 'reactions', 'replies.user']);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update([
            'body'      => $data['body'],
            'is_edited' => true,
        ]);

        return $comment->fresh(['user', 'reactions', 'replies.user']);
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }

    public function addReaction(Comment $comment, User $user, string $emoji): void
    {
        CommentReaction::firstOrCreate([
            'comment_id' => $comment->id,
            'user_id'    => $user->id,
            'emoji'      => $emoji,
        ]);
    }

    public function removeReaction(Comment $comment, User $user, string $emoji): void
    {
        CommentReaction::where([
            'comment_id' => $comment->id,
            'user_id'    => $user->id,
            'emoji'      => $emoji,
        ])->delete();
    }
}
