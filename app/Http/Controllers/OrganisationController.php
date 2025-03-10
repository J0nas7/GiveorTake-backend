<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OrganisationController extends Controller
{
    /**
     * Display a listing of organisations based on User ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getOrganisationsByUser(int $userId): JsonResponse
    {
        $organisation = Organisation::with('teams.projects') // Eager load teams and projects
            ->where('User_ID', $userId)
            ->first();

        if (!$organisation) {
            return response()->json(['message' => 'No organisations found for this user'], 404);
        }

        return response()->json($organisation);
    }

    //// The rest of this OrganisationController is RESTful API methods ////
    
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $organisations = Organisation::with('teams.userSeats')->get(); // Eager load teams and userSeat
        return response()->json($organisations); // Return organisations as JSON
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
            'User_ID' => 'required|integer',
            'Organisation_Name' => 'required|string|max:255',
            'Organisation_Description' => 'nullable|string',
        ]);

        $organisation = Organisation::create($validated); // Store the new organisation
        return response()->json($organisation, 201); // Return created organisation as JSON with HTTP status 201
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $organisation = Organisation::with('teams.userSeats')->find($id); // Eager load teams and userSeat

        if (!$organisation) {
            return response()->json(['message' => 'Organisation not found'], 404); // Return 404 if not found
        }

        return response()->json($organisation); // Return the organisation as JSON
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
            'User_ID' => 'required|integer',
            'Organisation_Name' => 'required|string|max:255',
            'Organisation_Description' => 'nullable|string',
        ]);

        $organisation = Organisation::find($id);

        if (!$organisation) {
            return response()->json(['message' => 'Organisation not found'], 404); // Return 404 if not found
        }

        $organisation->update($validated); // Update the organisation
        return response()->json($organisation); // Return the updated organisation as JSON
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $organisation = Organisation::find($id);

        if (!$organisation) {
            return response()->json(['message' => 'Organisation not found'], 404); // Return 404 if not found
        }

        $organisation->delete(); // Soft delete the organisation
        return response()->json(['message' => 'Organisation deleted successfully.']); // Return success message
    }
}
