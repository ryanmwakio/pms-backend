<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use App\Models\Issue;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends BaseController
{
    public function __construct(private readonly CommentService $service) {}

    public function index(Issue $issue): JsonResponse
    {
        $comments = $issue->comments()
            ->with(['user', 'reactions', 'replies.user', 'replies.reactions'])
            ->get();

        return $this->ok($comments);
    }

    public function store(Request $request, Issue $issue): JsonResponse
    {
        $data = $request->validate([
            'body'      => ['required', 'string', 'min:1'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:comments,id'],
        ]);

        return $this->created($this->service->create($issue, $data, $request->user()));
    }

    public function show(Comment $comment): JsonResponse
    {
        return $this->ok($comment->load(['user', 'reactions', 'replies.user']));
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1'],
        ]);

        return $this->ok($this->service->update($comment, $data));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);
        $this->service->delete($comment);

        return $this->noContent();
    }

    public function addReaction(Request $request, Comment $comment): JsonResponse
    {
        $data = $request->validate([
            'emoji' => ['required', 'string', 'max:10'],
        ]);

        $this->service->addReaction($comment, $request->user(), $data['emoji']);

        return $this->ok(null, 'Reaction added');
    }

    public function removeReaction(Request $request, Comment $comment, string $emoji): JsonResponse
    {
        $this->service->removeReaction($comment, $request->user(), $emoji);

        return $this->noContent();
    }
}
