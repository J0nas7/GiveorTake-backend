<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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

    protected function clearProjectCache($project): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$project->Project_ID}",
        ];

        Cache::deleteMultiple($keys);

        if ($project->Team_ID) {
            Cache::forget("projects:team:{$project->Team_ID}");
        }
    }

    protected function afterStore($project): void
    {
        $this->clearProjectCache($project);
    }

    protected function afterUpdate($project): void
    {
        $this->clearProjectCache($project);
    }

    protected function afterDestroy($project): void
    {
        $this->clearProjectCache($project);
    }

    /**
     * Custom: Get projects for a specific team, with Caching.
     *
     * @param  Team $team
     * @return JsonResponse
     */
    public function getProjectsByTeam(Team $team): JsonResponse
    {
        $cacheKey = "projects:team:{$team->Team_ID}";
        $cachedProjects = Cache::get($cacheKey);

        if ($cachedProjects) {
            $decodedProjects = json_decode($cachedProjects, true);
            $projects = collect($decodedProjects);
        } else {
            $projects = Project::where('Team_ID', $team->Team_ID)->get();

            if ($projects->isNotEmpty()) {
                Cache::put($cacheKey, $projects->toJson(), 600); // Cache 10 min
            }
        }

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this team'], 404);
        }

        // Apply permission filter
        $filteredProjects = PermissionHelper::filterByPermission($projects, 'accessProject', 'Project_ID');

        return response()->json($filteredProjects);
    }

    /**
     * Override index to force team-context.
     */
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Need a team-context to read projects']);
    }
}
