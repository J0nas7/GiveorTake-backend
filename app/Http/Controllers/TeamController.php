<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;

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
        // Invalidate cache if using Redis for caching teams
        // Redis::del([
        //     "teams:organisation:{$team->Organisation_ID}",
        //     'model:' . Str::snake(class_basename($this->modelClass)) . ':all',
        //     'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $team->Team_ID,
        // ]);
    }

    /**
     * Optional: Actions after destroy.
     */
    protected function afterDestroy($team): void
    {
        Redis::del([
            "teams:organisation:{$team->Organisation_ID}",
            'model:' . Str::snake(class_basename($this->modelClass)) . ':all',
            'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $team->Team_ID,
        ]);
    }

    /**
     * Display a listing of teams based on Organisation ID.
     *
     * @param int $organisationId
     * @return JsonResponse
     */
    public function getTeamsByOrganisation(int $organisationId): JsonResponse
    {
        // $cacheKey = "teams:organisation:{$organisationId}";

        // // Try to get from Redis
        // $cachedData = Redis::get($cacheKey);
        // if ($cachedData) {
        //     return response()->json(json_decode($cachedData, true));
        // }

        $teams = Team::with($this->with)
            ->where('Organisation_ID', $organisationId)
            ->get();

        if ($teams->isEmpty()) {
            return response()->json(['message' => 'No teams found for this organisation'], 404);
        }

        // Cache in Redis for 1 hour
        // Redis::setex($cacheKey, 3600, $teams->toJson());

        return response()->json($teams);
    }
}
