<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use Illuminate\Http\JsonResponse;
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
            'User_ID' => 'required|integer|exists:users,User_ID', // or the correct users table/column
            'Organisation_Name' => 'required|string|max:255',
            'Organisation_Description' => 'nullable|string',
        ];
    }

    protected function afterUpdate($organisation): void
    {
        Cache::forget("organisations:user:{$organisation->User_ID}");
    }

    protected function afterDestroy($organisation): void
    {
        Cache::forget("organisations:user:{$organisation->User_ID}");
    }

    /**
     * Display a listing of organisations based on User ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getOrganisationsByUser(int $userId): JsonResponse
    {
        // Try to get from Cache
        $cacheKey = "organisations:user:{$userId}";
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            $decodedData = json_decode($cachedData, true);
            return response()->json($decodedData);
        }

        // Get organisations where the user is the owner or the user is a member of a team within the organisation
        $organisations = Organisation::with(['teams.projects', 'teams.userSeats'])
            ->where('User_ID', $userId)  // Check if the user is the owner of the organisation
            ->orWhereHas('teams.userSeats', function ($query) use ($userId) {
                $query->where('User_ID', $userId);  // Check if the user has a seat in any team within the organisation
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
