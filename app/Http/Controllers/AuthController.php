<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use AuthService;

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $result = $this->registerUser($request->all());

        if (isset($result['errors'])) {
            return response()->json(['errors' => $result['errors']], 400);
        }

        return response()->json($result['user'], 201);
    }

    /**
     * Login a user and issue a JWT.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['User_Email', 'User_Password']);
        $result = $this->authenticateUser($credentials);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 401);
        }

        return response()->json(['token' => $result['token']], 200);
    }

    /**
     * Logout the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->logoutUser();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * Get details of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json($user, 200);
    }
}
?>