<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use UserService;

    /**
     * Get user by email.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = $this->findUserByEmail($request->input('email'));
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        return response()->json($user, 200);
    }

    //// The rest of this UserController is RESTful API methods ////

    /**
     * Get all users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = $this->getAllUsers();
        return response()->json($users, 200);
    }

    /**
     * Get a single user by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = $this->findUserById($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user, 200);
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $result = $this->createUser($request->all());

        if (isset($result['errors'])) {
            return response()->json(['errors' => $result['errors']], 400);
        }

        return response()->json($result['user'], 201);
    }

    /**
     * Update an existing user.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $this->findUserById($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $result = $this->updateUser($request->all(), $user);

        if (isset($result['errors'])) {
            return response()->json(['errors' => $result['errors']], 400);
        }

        return response()->json($result['user'], 200);
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = $this->findUserById($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $this->deleteUser($user);

        return response()->json(['message' => 'User deleted'], 200);
    }
}
?>