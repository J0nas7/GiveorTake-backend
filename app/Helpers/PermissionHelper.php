<?php

namespace App\Helpers;

use App\Models\Organisation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class PermissionHelper
{
    /**
     * Check if the current API user has the required dynamic permission.
     *
     * @param string $actionPrefix e.g., "accessProject", "manageBacklog"
     * @param int|string $resourceId
     *
     * @return JsonResponse|null Returns null if allowed, or a 403 response if not
     */
    public static function denyIfNoPermission(string $actionPrefix, $resourceId)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $permission = "{$actionPrefix}.{$resourceId}";

        $userId = $user->User_ID;
        if (in_array($actionPrefix, ['accessProject', 'manageProject', 'accessBacklog', 'manageBacklog'])) {
            $relation = null;
            $column = null;

            if (in_array($actionPrefix, ['accessProject', 'manageProject'])) {
                $relation = 'teams.projects';
                $column = 'Project_ID';
            } elseif (in_array($actionPrefix, ['accessBacklog', 'manageBacklog'])) {
                $relation = 'teams.projects.backlogs';
                $column = 'Backlog_ID';
            }

            if ($relation && $column) {
                $organisation = Organisation::where('User_ID', $userId)
                    ->whereHas($relation, function ($query) use ($resourceId, $column) {
                        $query->where($column, $resourceId);
                    })
                    ->exists();

                if ($organisation) {
                    return null;
                }
            }
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => "Forbidden - missing permission: {$permission}"
            ], 403);
        }

        return null; // user is authorized
    }

    public static function filterByPermission($collection, string $actionPrefix, string $key = 'id')
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        if (!$user) {
            return collect(); // No user = return empty collection
        }

        return $collection->filter(function ($item) use ($user, $actionPrefix, $key) {
            $resourceId = $item->{$key};
            $permission = "{$actionPrefix}.{$resourceId}";
            return $user->hasPermission($permission);
        });
    }
}
