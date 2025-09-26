<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file defines API routes for the application.
| - `apiResource()` is used for standard CRUD operations:
    GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
    apiResource handles all these CRUD routes
| - Custom routes are added below their corresponding resource.
| - Routes are grouped by middleware and functionality.
|
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BacklogController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskMediaFileController;
use App\Http\Controllers\TaskTimeTrackController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamUserSeatController;
use App\Http\Controllers\UtilityController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UserOnly;
use Illuminate\Support\Facades\App;

$privateApiMiddleware = ['auth:api', UserOnly::class];
$publicApiMiddleware = ['api'];

// Protected UserOnly Routes
Route::group(['middleware' => $privateApiMiddleware], function () {
    // ---- UserController Routes ----
    Route::apiResource('users', UserController::class);
    // Custom route to get user by user email
    Route::post('users/userByEmail', [UserController::class, 'getUserByEmail']);

    // ---- OrganisationController Routes ----
    Route::apiResource('organisations', OrganisationController::class);
    // Custom route to get organisations by user ID
    Route::prefix('organisations')->group(function () {
        Route::get('users/{user}', [OrganisationController::class, 'getOrganisationsByUser']);
    });

    // ---- TeamController Routes ----
    Route::apiResource('teams', TeamController::class);
    // Custom route to get teams by organisation ID
    Route::prefix('teams')->group(function () {
        Route::get('organisations/{organisation}', [TeamController::class, 'getTeamsByOrganisation']);
    });

    // ---- TeamUserSeatController Routes ----
    Route::apiResource('team-user-seats', TeamUserSeatController::class);
    Route::prefix('team-user-seats')->group(function () {
        // Custom route to find a seat by team ID and user ID
        Route::get('find/{team}/{user}', [TeamUserSeatController::class, 'findByTeamAndUser']);
        // Custom route to find all teams by user ID
        Route::get('teams-by-user/{user}', [TeamUserSeatController::class, 'findTeamsByUserID']);
        // Custom route to get all user seats by team ID
        Route::get('teams/{team}', [TeamUserSeatController::class, 'getTeamUserSeatsByTeamId']);
        // Custom route to get all roles and permissions by team ID
        Route::get('roles-permissions/teams/{team}', [TeamUserSeatController::class, 'getRolesAndPermissionsByTeamId']);
    });

    Route::prefix('team-roles')->group(function () {
        // Custom route to delete all roles and permissions by role ID
        Route::delete('{role}', [TeamUserSeatController::class, 'destroyTeamRole'])
            ->middleware(['auth:api', 'check.permission:Manage Team Members']);
        // Custom route creates a team role by team ID.
        Route::post('', [TeamUserSeatController::class, 'storeTeamRole'])
            ->middleware(['auth:api', 'check.permission:Manage Team Members']);
        // Custom route updates a team role by its ID.
        Route::put('{role}', [TeamUserSeatController::class, 'updateTeamRole'])
            ->middleware(['auth:api', 'check.permission:Manage Team Members']);
    });

    // ---- ProjectController Routes ----
    Route::apiResource('projects', ProjectController::class);
    Route::prefix('projects')->group(function () {
        // Custom route to get projects by team ID
        Route::get('teams/{team}', [ProjectController::class, 'getProjectsByTeam']);
    });

    // ---- BacklogController Routes ----
    Route::apiResource('backlogs', BacklogController::class);
    Route::prefix('backlogs')->group(function () {
        // Custom route to get backlogs by project ID
        Route::get('projects/{project}', [BacklogController::class, 'getBacklogsByProject']);
        // Custom route to finish a backlog
        Route::post('finish-backlog/{backlog}', [BacklogController::class, 'finishBacklog']);
    });

    // ---- StatusController Routes ----
    Route::apiResource('status', StatusController::class);
    Route::prefix('statuses')->group(function () {
        // Adjust the Status_Order of a given Status by moving it up or down within its backlog.
        Route::post('{statusId}/move-order', [StatusController::class, 'moveOrder']);
        // Assign the given status as the default for its backlog.
        Route::post('{statusId}/assign-default', [StatusController::class, 'assignDefault']);
        // Assign the given status as the closed for its backlog.
        Route::post('{statusId}/assign-closed', [StatusController::class, 'assignClosed']);
    });

    // ---- TaskController Routes ----
    Route::apiResource('tasks', TaskController::class);
    Route::prefix('tasks')->group(function () {
        // Custom route to get tasks by backlog ID
        Route::get('backlogs/{backlogId}', [TaskController::class, 'getTasksByBacklog']);
        // Custom route to bulk-delete tasks by array of IDs
        Route::post('bulk-destroy', [TaskController::class, 'bulkDestroy']);
        // Custom route to bulk-update tasks by array of data
        Route::post('bulk-update', [TaskController::class, 'bulkUpdate']);
        // Custom route to get task by keys
        Route::get('taskByKeys/{projectKey}/{taskKey}', [TaskController::class, 'getTaskByKeys']);
    });

    // ---- TaskTimeTrackController Routes ----
    Route::apiResource('task-time-tracks', TaskTimeTrackController::class);
    Route::prefix('task-time-tracks')->group(function () {
        // Custom route to get task-time-tracks by task ID
        Route::get('tasks/{taskId}', [TaskTimeTrackController::class, 'getTaskTimeTracksByTask']);
        // Custom route to get task-time-tracks by backlog ID
        Route::get('projects/{projectId}', [TaskTimeTrackController::class, 'getTaskTimeTracksByProject']);
        // Custom route to get the 10 latest unique TaskTimeTracks by project ID
        Route::get('latest/projects/{projectId}', [TaskTimeTrackController::class, 'getLatestUniqueTaskTimeTracksByBacklog']);
    });

    // ---- TaskCommentController Routes ----
    Route::apiResource('task-comments', TaskCommentController::class);
    Route::prefix('task-comments')->group(function () {
        // Custom route to get task-comments by task ID
        Route::get('tasks/{taskId}', [TaskCommentController::class, 'getCommentsByTask']);
    });

    // ---- TaskMediaFileController Routes ----
    Route::apiResource('task-media-files', TaskMediaFileController::class);
    Route::prefix('task-media-files')->group(function () {
        // Custom route to get task-media-files by task ID
        Route::get('tasks/{taskId}', [TaskMediaFileController::class, 'getMediaFilesByTask']);
    });

    // ---- UtilityController Routes ----
    // Custom route to get global search results
    Route::get('search/{searchString}', [UtilityController::class, 'search']);
});

// Public endpoints
Route::group(['middleware' => $publicApiMiddleware], function () {
    Route::get('/', function () {
        echo "test";
    });

    /**
     * AuthController Routes
     */
    Route::group(['middleware' => ['api']], function () {
        // Register a new user
        Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');

        // Login and generate JWT
        Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

        // Clone the token for the authenticated user
        Route::post('/auth/clone-token', [AuthController::class, 'cloneToken']);

        // Logout the authenticated user
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Get authenticated user details (requires authentication)
        Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:api')->name('auth.me');

        // Get authenticated user details (requires authentication)
        Route::get('/auth/refreshJWT', [AuthController::class, 'refreshJWT'])->middleware('auth:api');
    });
});
