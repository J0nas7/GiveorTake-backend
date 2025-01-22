<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\ProjectController;

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\UserOnly;

// Protected UserOnly Routes
Route::group(['middleware' => ['api', UserOnly::class]], function () {
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
    
    // AuthController Routes
    /**
     * Endpoints that require authentication.
     */
    Route::group(['middleware' => ['api']], function () {
        // Logout the authenticated user
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Get authenticated user details
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    });
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
    });
});
