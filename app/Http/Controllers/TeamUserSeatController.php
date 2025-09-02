<?php

namespace App\Http\Controllers;

use App\Models\TeamUserSeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TeamUserSeatController extends BaseController
{
    protected string $modelClass = TeamUserSeat::class;

    protected array $with = ['user', 'role'];

    protected function rules(): array
    {
        return [
            'Team_ID' => 'required|exists:GT_Teams,Team_ID',
            'User_ID' => 'required|exists:GT_Users,User_ID',
            'Role_ID' => 'required|exists:GT_Roles,Role_ID',
            'Seat_Status' => 'nullable|string|max:255',
            'Seat_Role_Description' => 'nullable|string|max:500',
            'Seat_Permissions' => 'nullable|json',
        ];
    }

    protected function clearTeamSeatCache($teamUserSeat): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$teamUserSeat->Team_ID}"
        ];

        Cache::deleteMultiple($keys);

        if ($teamUserSeat->Team_ID) {
            $keys = [
                "model:team:{$teamUserSeat->Team_ID}",
                "seats:team:{$teamUserSeat->Team_ID}"
            ];

            Cache::deleteMultiple($keys);
        }
    }

    protected function afterStore($teamUserSeat): void
    {
        $this->clearTeamSeatCache($teamUserSeat);
    }

    protected function afterUpdate($teamUserSeat): void
    {
        $this->clearTeamSeatCache($teamUserSeat);
    }

    protected function afterDestroy($teamUserSeat): void
    {
        $this->clearTeamSeatCache($teamUserSeat);
    }

    /**
     * Get all seats for a given team, bypassing BaseController caching for flexibility.
     */
    public function getTeamUserSeatsByTeamId(int $teamId)
    {
        $cacheKey = "seats:team:{$teamId}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $decodedData = json_decode($cached, true);
            return response()->json($decodedData);
        }

        $seats = $this->modelClass::where('Team_ID', $teamId)
            ->with($this->with)
            ->get();

        if ($seats->isEmpty()) {
            return response()->json(['message' => 'No user seats found for the specified team'], 404);
        }

        Cache::put($cacheKey, $seats->toJson(), 3600);

        return response()->json($seats);
    }

    /**
     * Update a seat with permission check.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $seat = $this->modelClass::with('team.organisation')->find($id);
        if (!$seat) {
            return response()->json(['message' => 'Seat not found'], 404);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();
        $isOwner = $user->User_ID === $seat->User_ID;
        $canManage = $user->hasPermission('Manage Team Members', $seat->team->organisation->Organisation_ID);

        if (!$canManage && !$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $canManage
            ? $request->validate($this->rules())
            : $request->validate(['Seat_Status' => 'required|string|max:255']);

        $seat->update($validated);

        // Clear team-specific cache
        $cacheKey = "seats:team:{$seat->Team_ID}";
        Cache::forget($cacheKey);

        return response()->json($seat);
    }

    /**
     * Delete a seat with permission check.
     */
    public function destroy(int $id): JsonResponse
    {
        $seat = $this->modelClass::with('team.organisation')->find($id);
        if (!$seat) {
            return response()->json(['message' => 'Seat not found'], 404);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();
        $isOwner = $user->User_ID === $seat->User_ID;
        $canManage = $user->hasPermission('Manage Team Members', $seat->team->organisation->Organisation_ID);

        if (!$canManage && !$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $seat->delete();

        // Clear team-specific cache
        $cacheKey = "seats:team:{$seat->Team_ID}";
        Cache::forget($cacheKey);

        return response()->json(['message' => 'Seat deleted successfully']);
    }
}
