<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OrganisationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $organisations = Organisation::all(); // Get all organisations
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
        $organisation = Organisation::find($id);

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
