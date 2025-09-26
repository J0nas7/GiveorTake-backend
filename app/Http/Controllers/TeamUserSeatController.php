<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamUserSeat;
use App\Models\User;
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
     * Find a seat based on Team ID and User ID.
     *
     * @param Team $team
     * @param User $user
     * @return JsonResponse
     */
    public function findByTeamAndUser(Team $team, User $user): JsonResponse
    {
        // Search for the seat based on Team ID and User ID
        $seat = TeamUserSeat::where('Team_ID', $team->Team_ID)
            ->where('User_ID', $user->User_ID)
            ->first(); // Get the first matching seat (there should be one or none)

        if (!$seat) {
            // Return 404 if no seat is found
            return response()->json(['message' => 'Seat not found for the specified team and user'], 404);
        }

        // Return the seat as JSON if found
        return response()->json($seat);
    }

    /**
     * Find all teams assigned to a specific user based on the User ID.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function findTeamsByUserID(User $user): JsonResponse
    {
        // Get all the teams where the user is assigned a seat
        $seats = TeamUserSeat::where('User_ID', $user->User_ID)
            ->with(['team.organisation', 'team.projects']) // Eager load the related Team, Organisation and Projects model
            ->get();

        if ($seats->isEmpty()) {
            // Return 404 if no teams are found for the user
            return response()->json(['message' => 'No teams found for the specified user'], 404);
        }

        // Return all teams associated with the user as JSON
        return response()->json($seats);
    }

    /**
     * Get all seats for a given team, bypassing BaseController caching for flexibility.
     *
     * @param Team $team
     * @return JsonResponse
     */
    public function getTeamUserSeatsByTeamId(Team $team)
    {
        $cacheKey = "seats:team:{$team->Team_ID}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $decodedData = json_decode($cached, true);
            return response()->json($decodedData);
        }

        $seats = $this->modelClass::where('Team_ID', $team->Team_ID)
            ->with($this->with)
            ->get();

        if ($seats->isEmpty()) {
            return response()->json(['message' => 'No user seats found for the specified team'], 404);
        }

        Cache::put($cacheKey, $seats->toJson(), 3600);

        return response()->json($seats);
    }

    /**
     * Retrieve all roles associated with a specific team by its ID.
     *
     * @param Team $team
     * @return JsonResponse
     */
    public function getRolesAndPermissionsByTeamId(Team $team): JsonResponse
    {
        // Retrieve all roles for the specified team, eager loading permissions
        $roles = Role::where('Team_ID', $team->Team_ID)
            ->with('permissions') // Eager load permissions
            ->get();

        if ($roles->isEmpty()) {
            return response()->json(['message' => 'No roles found for the specified team'], 404);
        }

        return response()->json($roles);
    }

    /**
     * Remove a role and its associated permissions by role ID.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function destroyTeamRole(Role $role): JsonResponse
    {
        $role->load('permissions');

        // Detach related permissions via pivot table
        $role->permissions()->detach();

        // Clear team-specific cache
        $cacheKey = "team:{$role->Team_ID}:seats";
        Cache::forget($cacheKey);

        $role->delete();

        return response()->json(['message' => 'Role and associated permissions removed successfully.']);
    }

    /**
     * Store a newly created team role and assign permissions.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request containing role data.
     * @return \Illuminate\Http\JsonResponse  JSON response with creation status and role data
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails.
     */
    public function storeTeamRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'Team_ID' => 'required|exists:GT_Teams,Team_ID',
            'Role_Name' => 'required|string|max:255',
            'Role_Description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*.Permission_Key' => 'required|string'
        ]);

        // Create the new role
        $role = Role::create([
            'Team_ID' => $validated['Team_ID'],
            'Role_Name' => $validated['Role_Name'],
            'Role_Description' => $validated['Role_Description'] ?? null,
        ]);

        // If permissions are provided, attach them
        if (!empty($validated['permissions'])) {
            $permissionKeys = collect($validated['permissions'])->pluck('Permission_Key')->all();
            $permissionIds = Permission::whereIn('Permission_Key', $permissionKeys)->pluck('Permission_ID')->toArray();

            // Attach permissions to the role
            $role->permissions()->sync($permissionIds);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role->load('permissions'),
        ], 201);
    }

    /**
     * Update the specified team role and its permissions.
     *
     * @param  Request $request
     * @param  Role $role
     * @return JsonResponse
     */
    public function updateTeamRole(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'Team_ID' => 'required|exists:GT_Teams,Team_ID',
            'Role_Name' => 'required|string|max:255',
            'Role_Description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*.Permission_Key' => 'required|string'
        ]);

        // Update Role info
        $role->update([
            'Team_ID' => $validated['Team_ID'],
            'Role_Name' => $validated['Role_Name'],
            'Role_Description' => $validated['Role_Description'] ?? null,
        ]);

        // Process permissions array (extract keys and fetch Permission_IDs)
        if (!empty($validated['permissions'])) {
            $permissionKeys = collect($validated['permissions'])->pluck('Permission_Key')->all();
            $permissionIds = Permission::whereIn('Permission_Key', $permissionKeys)->pluck('Permission_ID')->toArray();

            // Sync the permissions
            $role->permissions()->sync($permissionIds);
        }

        // Clear team-specific cache
        $cacheKey = "team:{$role->Team_ID}:seats";
        Cache::forget($cacheKey);

        return response()->json($role->load('permissions'));
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
