<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\TeamUserSeat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamUserSeatController extends Controller
{
    /**
     * Find a seat based on Team ID and User ID.
     *
     * @param int $team_id
     * @param int $user_id
     * @return JsonResponse
     */
    public function findByTeamAndUser(int $team_id, int $user_id): JsonResponse
    {
        // Search for the seat based on Team ID and User ID
        $seat = TeamUserSeat::where('Team_ID', $team_id)
            ->where('User_ID', $user_id)
            ->first(); // Get the first matching seat (there should be one or none)

        if (!$seat) {
            // Return 404 if no seat is found
            return response()->json(['message' => 'Seat not found for the specified team and user'], 404);
        }

        // Return the seat as JSON if found
        return response()->json($seat);
    }

    /**
     * Find all teams assigned to a specific user based on the User ID.
     *
     * @param int $user_id
     * @return JsonResponse
     */
    public function findTeamsByUserID(int $user_id): JsonResponse
    {
        // Get all the teams where the user is assigned a seat
        $seats = TeamUserSeat::where('User_ID', $user_id)
            ->with(['team.organisation', 'team.projects']) // Eager load the related Team, Organisation and Projects model
            ->get();

        if ($seats->isEmpty()) {
            // Return 404 if no teams are found for the user
            return response()->json(['message' => 'No teams found for the specified user'], 404);
        }

        // Return all teams associated with the user as JSON
        return response()->json($seats);
    }

    /**
     * Get all user seats for a specific Team ID.
     *
     * @param int $team_id
     * @return JsonResponse
     */
    public function getTeamUserSeatsByTeamId(int $teamId): JsonResponse
    {
        // Retrieve all user seats belonging to the specified Team ID
        $seats = TeamUserSeat::where('Team_ID', $teamId)
            ->with(['user', 'role']) // Eager load the User associated with each seat
            ->get();

        if ($seats->isEmpty()) {
            // Return 404 if no user seats are found for the team
            return response()->json(['message' => 'No user seats found for the specified team'], 404);
        }

        // Return the user seats as JSON response
        return response()->json($seats);
    }

    /**
     * Retrieve all roles associated with a specific team by its ID.
     *
     * @param int $teamId The ID of the team whose roles are to be retrieved.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the roles and their
     *                                       permissions, or an error message if no roles are found.
     */
    public function getRolesByTeamId(int $teamId): JsonResponse
    {
        // Retrieve all roles for the specified team, eager loading permissions
        $roles = Role::where('Team_ID', $teamId)
            ->with('permissions') // Eager load permissions
            ->get();

        if ($roles->isEmpty()) {
            return response()->json(['message' => 'No roles found for the specified team'], 404);
        }

        return response()->json($roles);
    }

    /**
     * Remove a role and its associated permissions by role ID.
     *
     * @param int $roleId The ID of the role to be removed.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation.
     */
    public function removeRolesAndPermissionsByRoleId(int $roleId): JsonResponse
    {
        $role = Role::with('permissions')->find($roleId);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Detach related permissions via pivot table
        $role->permissions()->detach();

        // Delete the role itself
        $role->delete();

        return response()->json(['message' => 'Role and associated permissions removed successfully.']);
    }

    //// The rest of this TeamUserSeatController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $seats = TeamUserSeat::with('team.organisation', 'user')->get(); // Eager load team and user
        return response()->json($seats); // Return as JSON
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'Team_ID' => 'required|exists:GT_Teams,Team_ID', // Ensure the team exists
            'User_ID' => 'required|exists:GT_Users,User_ID', // Ensure the user exists
            'Seat_Role' => 'required|string|max:255', // Role assignment
            'Seat_Status' => 'nullable|string|max:255', // Optional status
            'Seat_Role_Description' => 'nullable|string|max:500',
            'Seat_Permissions' => 'nullable|json',
        ]);

        $seat = TeamUserSeat::create($validated); // Create new seat assignment
        return response()->json($seat, 201); // Return the created seat
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $seat = TeamUserSeat::with('team.organisation', 'user')->find($id); // Eager load team and user

        if (!$seat) {
            return response()->json(['message' => 'Seat not found'], 404); // Return 404 if not found
        }

        return response()->json($seat); // Return the seat assignment
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'Team_ID' => 'required|exists:GT_Teams,Team_ID', // Ensure team exists
            'User_ID' => 'required|exists:GT_Users,User_ID', // Ensure user exists
            'Seat_Role' => 'required|string|max:255', // Role assignment
            'Seat_Status' => 'nullable|string|max:255', // Optional status
            'Seat_Role_Description' => 'nullable|string|max:500',
            'Seat_Permissions' => 'nullable|json',
        ]);

        $seat = TeamUserSeat::find($id);

        if (!$seat) {
            return response()->json(['message' => 'Seat not found'], 404); // Return 404 if not found
        }

        $seat->update($validated); // Update seat
        return response()->json($seat); // Return updated seat
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $seat = TeamUserSeat::find($id);

        if (!$seat) {
            return response()->json(['message' => 'Seat not found'], 404); // Return 404 if not found
        }

        $seat->delete(); // Soft delete the seat assignment
        return response()->json(['message' => 'Seat deleted successfully.']); // Return success message
    }
}
