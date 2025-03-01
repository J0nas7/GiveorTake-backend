<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects based on Team ID.
     *
     * @param int $teamId
     * @return JsonResponse
     */
    public function getProjectsByTeam(int $teamId): JsonResponse
    {
        $projects = Project::where('Team_ID', $teamId)->get();

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this team'], 404);
        }

        return response()->json($projects);
    }

    //// The rest of this ProjectController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $projects = Project::with('team', 'tasks')->get(); // Eager load team and user
        return response()->json($projects); // Return projects as JSON
    }

    /**
     * Show the form for creating a new resource.
     * This method is not typically used in an API.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Use the POST method to create a project.'], 405); // Method Not Allowed
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
            'Organisation_ID' => 'required|integer|exists:GT_Organisations,Organisation_ID', // Ensure the organisation exists
            'Project_Name' => 'required|string|max:255',
            'Project_Description' => 'nullable|string',
            'Project_Status' => 'required|string',
            'Project_Start_Date' => 'required|date',
            'Project_End_Date' => 'nullable|date',
        ]);

        $project = Project::create($validated); // Store the new project
        return response()->json($project, 201); // Return created project as JSON with HTTP status 201
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $project = Project::with('team.organisation', 'tasks')->find($id); // Eager load team and user

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404); // Return 404 if not found
        }

        return response()->json($project); // Return the project as JSON
    }

    /**
     * Show the form for editing the specified resource.
     * This method is not typically used in an API.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function edit(int $id): JsonResponse
    {
        return response()->json(['message' => 'Use the PUT or PATCH method to edit a project.'], 405); // Method Not Allowed
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
            'Project_Name' => 'required|string|max:255',
            'Project_Description' => 'nullable|string',
            'Project_Status' => 'required|string',
            'Project_Start_Date' => 'required|date',
            'Project_End_Date' => 'nullable|date',
        ]);

        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404); // Return 404 if not found
        }

        $project->update($validated); // Update the project
        return response()->json($project); // Return the updated project as JSON
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404); // Return 404 if not found
        }

        $project->delete(); // Soft delete the project
        return response()->json(['message' => 'Project deleted successfully.']); // Return success message
    }
}
