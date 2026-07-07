<?php

namespace App\Http\Controllers\Api;

use App\Models\PmsNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $unreadOnly = $request->boolean('unread_only');
        $user       = $request->user();

        return $this->ok([
            'notifications' => $this->service->getForUser($user, $unreadOnly),
            'unread_count'  => $this->service->unreadCount($user),
        ]);
    }

    public function markRead(Request $request, PmsNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $this->service->markRead($notification);

        return $this->ok(null, 'Marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->service->markAllRead($request->user());

        return $this->ok(null, 'All notifications marked as read');
    }

    public function destroy(Request $request, PmsNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->delete();

        return $this->noContent();
    }
}
