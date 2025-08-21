<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

class ProjectController extends BaseController
{
    protected string $modelClass = Project::class;

    protected array $with = [
        'team.organisation',
        'team.userSeats.user',
        'backlogs.tasks',
        'backlogs.statuses',
    ];

    public function __construct()
    {
        $this->middleware(['auth:api', 'check.permission:Modify Team Settings'])->only('store');
    }

    /**
     * Define validation rules for Project.
     */
    protected function rules(): array
    {
        return [
            'Team_ID' => 'required|integer|exists:GT_Teams,Team_ID',
            'Project_Name' => 'required|string|max:255',
            'Project_Key' => 'required|string|max:5',
            'Project_Description' => 'nullable|string',
            'Project_Status' => 'required|string',
            'Project_Start_Date' => 'required|date',
            'Project_End_Date' => 'nullable|date',
        ];
    }

    /**
     * Custom: Get projects for a specific team, with Redis caching.
     */
    public function getProjectsByTeam(int $teamId): JsonResponse
    {
        // $cacheKey = "team:{$teamId}:projects";

        // $cachedProjects = Redis::get($cacheKey);

        // if ($cachedProjects) {
        //     $projects = collect(json_decode($cachedProjects, true));
        // } else {
        $projects = Project::where('Team_ID', $teamId)->get();

        //     if ($projects->isNotEmpty()) {
        //         Redis::setex($cacheKey, 600, $projects->toJson()); // Cache 10 min
        //     }
        // }

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this team'], 404);
        }

        // Apply permission filter
        $filteredProjects = PermissionHelper::filterByPermission($projects, 'accessProject', 'Project_ID');

        return response()->json($filteredProjects);
    }

    /**
     * Hook: after project is created, clear team cache.
     */
    protected function afterStore($resource): void
    {
        // Redis::del("team:{$resource->Team_ID}:projects");
    }

    /**
     * Hook: after project is updated, clear project + team cache.
     */
    protected function afterUpdate($resource): void
    {
        // Redis::del([
        //     "model:project:{$resource->Project_ID}",
        //     "team:{$resource->Team_ID}:projects"
        // ]);
    }

    /**
     * Hook: after project is deleted, clear project + team cache.
     */
    protected function afterDestroy($resource): void
    {
        // Redis::del([
        //     "model:project:{$resource->Project_ID}",
        //     "team:{$resource->Team_ID}:projects"
        // ]);
    }

    /**
     * Override index to force team-context.
     */
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Need a team-context to read projects']);
    }
}
