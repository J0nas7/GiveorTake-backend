<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class TeamController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'check.permission:Modify Organisation Settings'])->only('store');
        $this->middleware(['auth:api', 'check.permission:Modify Team Settings'])->only(['update', 'destroy']);
    }

    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = Team::class;

    /**
     * The relationships to eager load when fetching teams.
     *
     * @var array
     */
    protected array $with = [
        'organisation',
        'projects.backlogs'
    ];

    /**
     * Define the validation rules for teams.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'Organisation_ID' => 'required|integer|exists:organisations,Organisation_ID',
            'Team_Name' => 'required|string|max:255',
            'Team_Description' => 'nullable|string',
        ];
    }

    protected function clearTeamCache($team): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$team->Team_ID}"
        ];

        Cache::deleteMultiple($keys);

        if ($team->Organisation_ID) {
            $keys = [
                "model:organisation:{$team->Organisation_ID}",
                "teams:organisation:{$team->Organisation_ID}"
            ];

            Cache::deleteMultiple($keys);
        }
    }

    protected function afterStore($team): void
    {
        $this->clearTeamCache($team);
    }

    protected function afterUpdate($team): void
    {
        $this->clearTeamCache($team);
    }

    protected function afterDestroy($team): void
    {
        $this->clearTeamCache($team);
    }

    /**
     * Display a listing of teams based on Organisation ID.
     *
     * @param Organisation $organisation
     * @return JsonResponse
     */
    public function getTeamsByOrganisation(Organisation $organisation): JsonResponse
    {
        // Try to get from Cache
        $cacheKey = "teams:organisation:{$organisation->Organisation_ID}";
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            $decodedData = json_decode($cachedData, true);
            return response()->json($decodedData);
        }

        $teams = Team::with($this->with)
            ->where('Organisation_ID', $organisation->Organisation_ID)
            ->get();

        if ($teams->isEmpty()) {
            return response()->json(['message' => 'No teams found for this organisation'], 404);
        }

        // Cache for 1 hour
        Cache::put($cacheKey, $teams->toJson(), 3600);

        return response()->json($teams);
    }
}
