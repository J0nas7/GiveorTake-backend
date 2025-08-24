<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
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

    /**
     * Optional: Actions after update.
     */
    protected function afterUpdate($team): void
    {
        // Invalidate cache if using Cache for caching teams
        $keys = [
            "teams:organisation:{$team->Organisation_ID}",
            'model:' . Str::snake(class_basename($this->modelClass)) . ':all',
            'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $team->Team_ID,
        ];
        Cache::deleteMultiple($keys);
    }

    /**
     * Optional: Actions after destroy.
     */
    protected function afterDestroy($team): void
    {
        $keys = [
            "teams:organisation:{$team->Organisation_ID}",
            'model:' . Str::snake(class_basename($this->modelClass)) . ':all',
            'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $team->Team_ID,
        ];
        Cache::deleteMultiple($keys);
    }

    /**
     * Display a listing of teams based on Organisation ID.
     *
     * @param int $organisationId
     * @return JsonResponse
     */
    public function getTeamsByOrganisation(int $organisationId): JsonResponse
    {
        // Try to get from Cache
        $cacheKey = "teams:organisation:{$organisationId}";
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            $decodedData = json_decode($cachedData, true);
            return response()->json($decodedData);
        }

        $teams = Team::with($this->with)
            ->where('Organisation_ID', $organisationId)
            ->get();

        if ($teams->isEmpty()) {
            return response()->json(['message' => 'No teams found for this organisation'], 404);
        }

        // Cache for 1 hour
        Cache::put($cacheKey, $teams->toJson(), 3600);

        return response()->json($teams);
    }
}
