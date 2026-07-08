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
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WorkspaceService $workspaceService,
    ) {}

    #[OA\Post(
        path: '/auth/register',
        operationId: 'register',
        summary: 'Register a new user',
        tags: ['Auth'],
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'password_confirmation', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'User registered successfully',
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
    )]
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

    #[OA\Post(
        path: '/auth/login',
        operationId: 'login',
        summary: 'Login a user',
        tags: ['Auth'],
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
            ],
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'User logged in successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials',
    )]
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

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'logout',
        summary: 'Logout a user',
        tags: ['Auth'],
    )]
    #[OA\Response(
        response: 200,
        description: 'User logged out successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden',
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logged out');
    }

    #[OA\Get(
        path: '/auth/me',
        operationId: 'me',
        summary: 'Get current user',
        tags: ['Auth'],
    )]
    #[OA\Response(
        response: 200,
        description: 'User retrieved successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden',
    )]
    public function me(Request $request): JsonResponse
    {
        return $this->ok(
            $request->user()->load('activeWorkspace')
        );
    }

    #[OA\Put(
        path: '/auth/profile',
        operationId: 'updateProfile',
        summary: 'Update user profile',
        tags: ['Auth'],
    )]
    #[OA\Response(
        response: 200,
        description: 'User profile updated successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden',
    )]
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

    #[OA\Put(
        path: '/auth/password',
        operationId: 'updatePassword',
        summary: 'Update user password',
        tags: ['Auth'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Password updated successfully',
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden',
    )]
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
