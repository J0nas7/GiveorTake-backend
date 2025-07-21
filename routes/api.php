<?php

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

// Protected UserOnly Routes
Route::group(['middleware' => ['auth:api', UserOnly::class]], function () {
    /** UserController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('users', UserController::class);
    // Custom route to get user by user email
    Route::post('users/userByEmail', [UserController::class, 'getUserByEmail']);



    /** OrganisationController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('organisations', OrganisationController::class);
    // Custom route to get organisations by user ID1
    Route::get('users/{userId}/organisations', [OrganisationController::class, 'getOrganisationsByUser']);



    /** TeamController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('teams', TeamController::class);
    // Custom route to get teams by organisation ID
    Route::get('organisations/{organisationId}/teams', [TeamController::class, 'getTeamsByOrganisation']);



    /** TeamUserSeatController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('team-user-seats', TeamUserSeatController::class);
    // Custom route to find a seat by team ID and user ID
    Route::get('team-user-seats/find/{team_id}/{user_id}', [TeamUserSeatController::class, 'findByTeamAndUser']);
    // Custom route to find all teams by user ID
    Route::get('team-user-seats/teams-by-user/{user_id}', [TeamUserSeatController::class, 'findTeamsByUserID']);
    // Custom route to get all user seats by team ID
    Route::get('teams/{teamId}/team-user-seats', [TeamUserSeatController::class, 'getTeamUserSeatsByTeamId']);
    // Custom route to get all roles and permissions by team ID
    Route::get('teams/{teamId}/team-roles-permissions', [TeamUserSeatController::class, 'getRolesByTeamId']);
    // Custom route to delete all roles and permissions by role ID
    Route::delete('team-roles/{teamRoleId}', [TeamUserSeatController::class, 'destroyTeamRole'])
        ->middleware(['auth:api', 'check.permission:Manage Team Members']);
    // Custom route creates a team role by team ID.
    Route::post('team-roles', [TeamUserSeatController::class, 'storeTeamRole'])
        ->middleware(['auth:api', 'check.permission:Manage Team Members']);
    // Custom route updates a team role by its ID.
    Route::put('team-roles/{teamRoleId}', [TeamUserSeatController::class, 'updateTeamRole'])
        ->middleware(['auth:api', 'check.permission:Manage Team Members']);



    /** ProjectController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('projects', ProjectController::class);
    // Custom route to get projects by team ID
    Route::get('teams/{teamId}/projects', [ProjectController::class, 'getProjectsByTeam']);



    /** BacklogController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('backlogs', BacklogController::class);
    // Custom route to get backlogs by project ID
    Route::get('projects/{projectId}/backlogs', [BacklogController::class, 'getBacklogsByProject']);
    // Custom route to finish a backlog
    Route::post('finish-backlog/{backlogId}', [BacklogController::class, 'finishBacklog']);



    /** StatusController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('status', StatusController::class);
    // Adjust the Status_Order of a given Status by moving it up or down within its backlog.
    Route::post('/statuses/{id}/move-order', [StatusController::class, 'moveOrder']);
    // Assign the given status as the default for its backlog.
    Route::post('statuses/{id}/assign-default', [StatusController::class, 'assignDefault']);
    // Assign the given status as the closed for its backlog.
    Route::post('statuses/{id}/assign-closed', [StatusController::class, 'assignClosed']);




    /** TaskController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('tasks', TaskController::class);
    // Custom route to get tasks by backlog ID
    Route::get('backlogs/{backlogId}/tasks', [TaskController::class, 'getTasksByBacklog']);
    // Custom route to bulk-delete tasks by array of IDs
    Route::post('/tasks/bulk-destroy', [TaskController::class, 'bulkDestroy']);
    // Custom route to bulk-update tasks by array of data
    Route::post('/tasks/bulk-update', [TaskController::class, 'bulkUpdate']);
    // Custom route to get task by keys
    Route::get('taskByKeys/{projectKey}/{taskKey}', [TaskController::class, 'getTaskByKeys']);

    /** TaskTimeTrackController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('task-time-tracks', TaskTimeTrackController::class);
    // Custom route to get task-time-tracks by task ID
    Route::get('tasks/{taskId}/task-time-tracks', [TaskTimeTrackController::class, 'getTaskTimeTracksByTask']);
    // Custom route to get task-time-tracks by backlog ID
    Route::get('projects/{projectId}/task-time-tracks', [TaskTimeTrackController::class, 'getTaskTimeTracksByProject']);
    // Custom route to get the 10 latest unique TaskTimeTracks by Project_ID
    Route::get('projects/{projectId}/latest-task-time-tracks', [TaskTimeTrackController::class, 'getLatestUniqueTaskTimeTracksByBacklog']);


    /** TaskCommentController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('task-comments', TaskCommentController::class);
    // Custom route to get task-comments by task ID
    Route::get('tasks/{taskId}/task-comments', [TaskCommentController::class, 'getCommentsByTask']);



    /** TaskMediaFileController Routes
     * GET (index) - POST (store) - GET (show) - PUT (update) - DELETE (destroy)
     * This single line of code handles all these CRUD routes: */
    Route::apiResource('task-media-files', TaskMediaFileController::class);
    // Custom route to get task-media-files by task ID
    Route::get('tasks/{taskId}/task-media-files', [TaskMediaFileController::class, 'getMediaFilesByTask']);

    // UtilityController Routes
    // Custom route to get global search results
    Route::get('search/{userId}/{searchString}', [UtilityController::class, 'globalSearch']);
});



// Public endpoints
Route::group(['middleware' => ['api']], function () {
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
