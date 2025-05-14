<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    /**
     * Display a listing of teams based on Organisation ID.
     *
     * @param int $organisationId
     * @return JsonResponse
     */
    public function getTeamsByOrganisation(int $organisationId): JsonResponse
    {
        $teams = Team::where('Organisation_ID', $organisationId)->get();

        if ($teams->isEmpty()) {
            return response()->json(['message' => 'No teams found for this organisation'], 404);
        }

        return response()->json($teams);
    }

    //// The rest of this TeamController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $teams = Team::with('organisation', 'projects.backlogs')->get(); // Eager load organisation and projects
        return response()->json($teams); // Return teams as JSON
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
            'Organisation_ID' => 'required|integer',
            'Team_Name' => 'required|string|max:255',
            'Team_Description' => 'nullable|string',
        ]);

        $team = Team::create($validated); // Store the new team
        return response()->json($team, 201); // Return created team as JSON with HTTP status 201
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $team = Team::with('organisation', 'projects.backlogs')->find($id); // Eager load organisation and projects

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404); // Return 404 if not found
        }

        return response()->json($team); // Return the team as JSON
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
            'Organisation_ID' => 'required|integer',
            'Team_Name' => 'required|string|max:255',
            'Team_Description' => 'nullable|string',
        ]);

        $team = Team::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404); // Return 404 if not found
        }

        $team->update($validated); // Update the team
        return response()->json($team); // Return the updated team as JSON
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404); // Return 404 if not found
        }

        $team->delete(); // Delete the team
        return response()->json(['message' => 'Team deleted successfully.']); // Return success message
    }
}
