<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'check.permission:Modify Team Settings'])->only('store');
    }

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

        // Filter to only include accessible projects
        $filteredProjects = PermissionHelper::filterByPermission($projects, 'accessProject', 'Project_ID');

        return response()->json($filteredProjects);
    }

    //// The rest of this ProjectController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Need a team-context to read projects']);
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
            'Team_ID' => 'required|integer|exists:GT_Teams,Team_ID', // Ensure the team exists
            'Project_Name' => 'required|string|max:255',
            'Project_Key' => 'required|string|max:5',
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
        $project = Project::with([
            'team.organisation',  // Load the organisation related to the team
            'team.userSeats.user',    // Load the user seats within the team
            'backlogs.tasks',               // Load tasks under the project
            'backlogs.statuses'               // Load statuses under the backlog
        ])->find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404); // Return 404 if not found
        }

        // Permission check
        if ($response = PermissionHelper::denyIfNoPermission('accessProject', $project->Project_ID)) {
            return $response;
        }

        return response()->json($project); // Return the project as JSON
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
            'Project_Key' => 'required|string|max:5',
            'Project_Description' => 'nullable|string',
            'Project_Status' => 'required|string',
            'Project_Start_Date' => 'required|date',
            'Project_End_Date' => 'nullable|date',
        ]);

        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404); // Return 404 if not found
        }

        // Permission check
        if ($response = PermissionHelper::denyIfNoPermission('manageProject', $project->Project_ID)) {
            return $response;
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

        // Permission check
        if ($response = PermissionHelper::denyIfNoPermission('manageProject', $project->Project_ID)) {
            return $response;
        }

        $project->delete(); // Soft delete the project
        return response()->json(['message' => 'Project deleted successfully.']); // Return success message
    }
}
