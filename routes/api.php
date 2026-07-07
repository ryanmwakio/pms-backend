<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\SprintController;
use App\Http\Controllers\Api\EpicController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────
// Auth
// ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateProfile']);
        Route::put('me/password', [AuthController::class, 'updatePassword']);
    });
});

// ──────────────────────────────────────────────
// Authenticated routes
// ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Workspaces
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('workspaces/{workspace}/members', [WorkspaceController::class, 'addMember']);
    Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember']);

    // Users (workspace-scoped)
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);

    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::post('projects/{project}/favorite', [ProjectController::class, 'toggleFavorite']);
    Route::get('projects/{project}/members', [ProjectController::class, 'members']);
    Route::post('projects/{project}/members', [ProjectController::class, 'addMember']);
    Route::delete('projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);

    // Epics
    Route::apiResource('projects/{project}/epics', EpicController::class)->shallow();

    // Sprints
    Route::apiResource('projects/{project}/sprints', SprintController::class)->shallow();
    Route::post('sprints/{sprint}/start', [SprintController::class, 'start']);
    Route::post('sprints/{sprint}/complete', [SprintController::class, 'complete']);
    Route::post('sprints/{sprint}/issues/{issue}', [SprintController::class, 'addIssue']);
    Route::delete('sprints/{sprint}/issues/{issue}', [SprintController::class, 'removeIssue']);

    // Issues
    Route::apiResource('projects/{project}/issues', IssueController::class)->shallow();
    Route::post('issues/{issue}/duplicate', [IssueController::class, 'duplicate']);
    Route::post('issues/{issue}/move', [IssueController::class, 'move']);
    Route::post('issues/{issue}/watchers', [IssueController::class, 'addWatcher']);
    Route::delete('issues/{issue}/watchers', [IssueController::class, 'removeWatcher']);
    Route::post('issues/{issue}/links', [IssueController::class, 'addLink']);
    Route::delete('issues/{issue}/links/{linked}', [IssueController::class, 'removeLink']);
    Route::post('issues/bulk', [IssueController::class, 'bulk']);
    Route::get('issues/{issue}/activity', [IssueController::class, 'activity']);

    // Comments
    Route::apiResource('issues/{issue}/comments', CommentController::class)->shallow();
    Route::post('comments/{comment}/reactions', [CommentController::class, 'addReaction']);
    Route::delete('comments/{comment}/reactions/{emoji}', [CommentController::class, 'removeReaction']);

    // Labels
    Route::apiResource('labels', LabelController::class);

    // Statuses
    Route::apiResource('projects/{project}/statuses', StatusController::class)->shallow();
    Route::post('projects/{project}/statuses/reorder', [StatusController::class, 'reorder']);

    // Teams
    Route::apiResource('teams', TeamController::class);
    Route::post('teams/{team}/members', [TeamController::class, 'addMember']);
    Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember']);

    // Dashboard
    Route::get('projects/{project}/dashboard', [DashboardController::class, 'show']);

    // Reports
    Route::get('projects/{project}/reports/overview', [ReportController::class, 'overview']);
    Route::get('projects/{project}/reports/burndown', [ReportController::class, 'burndown']);
    Route::get('projects/{project}/reports/velocity', [ReportController::class, 'velocity']);
    Route::get('projects/{project}/reports/cycle-time', [ReportController::class, 'cycleTime']);
    Route::get('projects/{project}/reports/team-performance', [ReportController::class, 'teamPerformance']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    // Activity
    Route::get('activity', [ActivityController::class, 'index']);
    Route::get('projects/{project}/activity', [ActivityController::class, 'projectActivity']);
});
