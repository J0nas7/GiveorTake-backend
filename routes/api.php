<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskMediaFileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamUserSeatController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UserOnly;

// Protected UserOnly Routes
Route::group(['middleware' => ['auth:api', UserOnly::class]], function () {
    // UserController Routes
    /**
     * GET /users - index
     * POST /users - store
     * GET /users/{property} - show
     * PUT /users/{property} - update
     * DELETE /users/{property} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('users', UserController::class);


    
    // OrganisationController Routes
    /**
     * GET /organisations - index
     * POST /organisations - store
     * GET /organisations/{organisation} - show
     * PUT /organisations/{organisation} - update
     * DELETE /organisations/{organisation} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('organisations', OrganisationController::class);



    // TeamController Routes
    /**
     * GET /teams - index
     * POST /teams - store
     * GET /teams/{team} - show
     * PUT /teams/{team} - update
     * DELETE /teams/{team} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('teams', TeamController::class);
    // Custom route to get teams by organisation ID
    Route::get('organisations/{organisationId}/teams', [TeamController::class, 'getTeamsByOrganisation']);



    // TeamUserSeatController Routes
    /**
     * GET /team-user-seats - index
     * POST /team-user-seats - store
     * GET /team-user-seats/{team-user-seat} - show
     * PUT /team-user-seats/{team-user-seat} - update
     * DELETE /team-user-seats/{team-user-seat} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('team-user-seats', TeamUserSeatController::class);
    // Custom route to find a seat by team ID and user ID
    Route::get('team-user-seats/find/{team_id}/{user_id}', [TeamUserSeatController::class, 'findByTeamAndUser']);
    // Custom route to find all teams by user ID
    Route::get('team-user-seats/teams-by-user/{user_id}', [TeamUserSeatController::class, 'findTeamsByUserID']);
    // Custom route to get all user seats by team ID
    Route::get('teams/{teamId}/team-user-seats', [TeamUserSeatController::class, 'getTeamUserSeatsByTeamId']);

    
    
    // ProjectController Routes
    /**
     * GET /projects - index
     * POST /projects - store
     * GET /projects/{project} - show
     * PUT /projects/{project} - update
     * DELETE /projects/{project} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('projects', ProjectController::class);
    // Custom route to get projects by team ID
    Route::get('teams/{teamId}/projects', [ProjectController::class, 'getProjectsByTeam']);

    
    
    // TaskController Routes
    /**
     * GET /tasks - index
     * POST /tasks - store
     * GET /tasks/{task} - show
     * PUT /tasks/{task} - update
     * DELETE /tasks/{task} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('tasks', TaskController::class);
    // Custom route to get tasks by project ID
    Route::get('projects/{projectId}/tasks', [TaskController::class, 'getTasksByProject']);

    // TaskCommentController Routes
    /**
     * GET /task-comments - index
     * POST /task-comments - store
     * GET /task-comments/{taskComment} - show
     * PUT /task-comments/{taskComment} - update
     * DELETE /task-comments/{taskComment} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('task-comments', TaskCommentController::class);
    // Custom route to get task-comments by task ID
    Route::get('tasks/{taskId}/task-comments', [TaskCommentController::class, 'getCommentsByTask']);
    
    // TaskMediaFileController Routes
    /**
     * GET /task-media-files - index
     * POST /task-media-files - store
     * GET /task-media-files/{taskMediaFile} - show
     * PUT /task-media-files/{taskMediaFile} - update
     * DELETE /task-media-files/{taskMediaFile} - destroy
     * This single line of code handles all these CRUD routes:
     */
    Route::apiResource('task-media-files', TaskMediaFileController::class);
    // Custom route to get task-media-files by task ID
    Route::get('tasks/{taskId}/task-media-files', [TaskMediaFileController::class, 'getMediaFilesByTask']);

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
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

        // Logout the authenticated user
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Get authenticated user details (requires authentication)
        Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:api')->name('auth.me');
    });
});
