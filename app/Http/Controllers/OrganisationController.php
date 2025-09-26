<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class OrganisationController extends BaseController
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'check.permission:Modify Organisation Settings'])
            ->only(['update', 'destroy']);
    }

    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = Organisation::class;

    /**
     * The relationships to eager load when fetching organisations.
     *
     * @var array
     */
    protected array $with = [
        'teams.userSeats'
    ];

    /**
     * Define the validation rules for organisations.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'User_ID' => 'required|integer|exists:GT_Users,User_ID', // or the correct users table/column
            'Organisation_Name' => 'required|string|max:255',
            'Organisation_Description' => 'nullable|string',
        ];
    }

    protected function clearOrganisationCache($organisation): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$organisation->Organisation_ID}",
        ];

        Cache::deleteMultiple($keys);

        if ($organisation->User_ID) {
            Cache::forget("organisations:user:{$organisation->User_ID}");
        }
    }

    protected function afterStore($organisation): void
    {
        $this->clearOrganisationCache($organisation);
    }

    protected function afterUpdate($organisation): void
    {
        $this->clearOrganisationCache($organisation);
    }

    protected function afterDestroy($organisation): void
    {
        $this->clearOrganisationCache($organisation);
    }

    /**
     * Display a listing of organisations based on User ID.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function getOrganisationsByUser(User $user): JsonResponse
    {
        // Try to get from Cache
        $cacheKey = "organisations:user:{$user->User_ID}";
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            $decodedData = json_decode($cachedData, true);
            return response()->json($decodedData);
        }

        // Get organisations where the user is the owner or the user is a member of a team within the organisation
        $organisations = Organisation::with(['teams.projects', 'teams.userSeats'])
            ->where('User_ID', $user->User_ID)
            ->orWhereHas('teams.userSeats', function ($query) use ($user) {
                $query->where('User_ID', $user->User_ID);  // Check if the user has a seat in any team within the organisation
            })
            ->get();

        if ($organisations->isEmpty()) {
            return response()->json(['message' => 'No organisations found for this user'], 404);
        }

        // Cache for 1 hour
        Cache::put($cacheKey, $organisations->toJson(), 3600);

        return response()->json($organisations);
    }
}
