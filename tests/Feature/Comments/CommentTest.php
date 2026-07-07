<?php

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;

describe('Comments', function () {

    it('lists comments for an issue', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);

        Comment::factory()->count(3)->create(['issue_id' => $issue->id, 'user_id' => $user->id]);

        $this->withToken($token)
            ->getJson("/api/issues/{$issue->id}/comments")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('creates a comment and auto-watches the issue', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);

        $this->withToken($token)
            ->postJson("/api/issues/{$issue->id}/comments", [
                'body' => 'Great progress on this one!',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.body', 'Great progress on this one!');

        $this->assertDatabaseHas('issue_watchers', ['issue_id' => $issue->id, 'user_id' => $user->id]);
    });

    it('updates own comment', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue   = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);
        $comment = Comment::factory()->create(['issue_id' => $issue->id, 'user_id' => $user->id, 'body' => 'Old text']);

        $this->withToken($token)
            ->putJson("/api/comments/{$comment->id}", ['body' => 'Updated text'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Updated text')
            ->assertJsonPath('data.is_edited', true);
    });

    it('cannot update another users comment', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $other   = User::factory()->create();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue   = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);
        $comment = Comment::factory()->create(['issue_id' => $issue->id, 'user_id' => $other->id]);

        $this->withToken($token)
            ->putJson("/api/comments/{$comment->id}", ['body' => 'Hacked!'])
            ->assertStatus(403);
    });

    it('deletes own comment', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue   = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);
        $comment = Comment::factory()->create(['issue_id' => $issue->id, 'user_id' => $user->id]);

        $this->withToken($token)
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    });

    it('adds and removes a reaction to a comment', function () {
        [$user, $workspace, $token] = actingAsNewUser();
        $project = createProject($workspace, $user);
        $status  = defaultStatus($project);

        $issue   = Issue::factory()->create(['project_id' => $project->id, 'status_id' => $status->id, 'reporter_id' => $user->id, 'key' => 'TST-1']);
        $comment = Comment::factory()->create(['issue_id' => $issue->id, 'user_id' => $user->id]);

        $this->withToken($token)
            ->postJson("/api/comments/{$comment->id}/reactions", ['emoji' => '👍'])
            ->assertOk();

        $this->assertDatabaseHas('comment_reactions', ['comment_id' => $comment->id, 'user_id' => $user->id, 'emoji' => '👍']);

        $this->withToken($token)
            ->deleteJson("/api/comments/{$comment->id}/reactions/👍")
            ->assertStatus(204);

        $this->assertDatabaseMissing('comment_reactions', ['comment_id' => $comment->id, 'user_id' => $user->id, 'emoji' => '👍']);
    });
});
