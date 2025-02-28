<?php

namespace App\Http\Controllers;

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
            ->with('team') // Eager load the related Team model (ensure Team model is defined)
            ->get();

        if ($seats->isEmpty()) {
            // Return 404 if no teams are found for the user
            return response()->json(['message' => 'No teams found for the specified user'], 404);
        }

        // Return all teams associated with the user as JSON
        return response()->json($seats);
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
        $seat = TeamUserSeat::find($id);

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
?>