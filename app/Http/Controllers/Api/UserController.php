<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Workspace;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $service) {}

    public function index(Request $request): JsonResponse
    {
        $workspace = Workspace::findOrFail($this->currentWorkspaceId());

        $users = $this->service->listForWorkspace($workspace);

        // Optional search
        if ($search = $request->input('search')) {
            $users = $users->filter(
                fn ($u) => str_contains(strtolower($u->name), strtolower($search))
                    || str_contains(strtolower($u->email), strtolower($search))
            )->values();
        }

        return $this->ok($users);
    }

    public function show(User $user): JsonResponse
    {
        return $this->ok($user->load(['teams', 'projects']));
    }
}
