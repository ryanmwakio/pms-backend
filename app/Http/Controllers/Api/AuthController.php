<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\UserService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends BaseController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WorkspaceService $workspaceService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $parts = explode(' ', trim($data['name']));
        $initials = strtoupper(
            substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );

        $user = User::create([
            'name'            => $data['name'],
            'email'           => $data['email'],
            'password'        => Hash::make($data['password']),
            'avatar_initials' => $initials,
            'avatar_color'    => collect(['#4264f5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'])->random(),
        ]);

        // Create a default workspace for the new user
        $workspace = $this->workspaceService->create([
            'name' => "{$user->name}'s Workspace",
        ], $user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->created([
            'user'      => $user->fresh(),
            'workspace' => $workspace,
            'token'     => $token,
        ], 'Registration successful');
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return $this->error('Invalid credentials', 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->ok([
            'user'  => $user->load('activeWorkspace'),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logged out');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()->load('activeWorkspace')
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'role'       => ['sometimes', 'string', 'max:100'],
            'timezone'   => ['sometimes', 'string', 'timezone'],
            'theme'      => ['sometimes', 'in:light,dark,system'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
        ]);

        $user = $this->userService->updateProfile($request->user(), $data);

        return $this->ok($user);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->userService->updatePassword(
            $request->user(),
            $data['current_password'],
            $data['password']
        );

        return $this->ok(null, 'Password updated');
    }
}
