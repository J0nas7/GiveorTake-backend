<?php

namespace App\Http\Middleware;

use App\Models\Backlog;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    // Resolves a permission string by replacing placeholders with actual values from the request.
    /**
     * @param Request $request The HTTP request object containing route and input data.
     * @param string $pattern The permission string pattern with placeholders in the format {param}.
     * @return string The resolved permission string with placeholders replaced by actual values.
     */
    protected function resolvePermissionString(Request $request, string $pattern): string
    {
        // Use regular expression to find all placeholders in the pattern
        preg_match_all('/\{(\w+)\}/', $pattern, $matches);

        // Loop through each matched placeholder
        foreach ($matches[1] ?? [] as $param) {
            // Retrieve the value for the placeholder from route parameters or input data
            $value = $request->route($param) ?? $request->input($param);

            // If the value is not found, abort with a 400 error response
            if (!$value) {
                abort(response()->json(['message' => "Missing route or input parameter: {$param}"], 400));
            }

            // Replace the placeholder in the pattern with the actual value
            $pattern = str_replace("{{$param}}", $value, $pattern);
        }

        // Return the resolved permission string
        return $pattern;
    }

    // Checks if a resource with the given ID is owned by the user.
    /**
     * @param mixed $id The ID of the resource to check.
     * @param string $model The model class of the resource.
     * @param string|null $with The relationships to eager load.
     * @param mixed $user The user to check ownership against.
     * @return bool True if the resource exists and is owned by the user, false otherwise.
     */
    private function checkOwnership($id, $model, $with, $user)
    {
        try {
            // Initialize the query for the given model
            $query = $model::query();

            // Eager load the specified relationships if any
            if ($with) {
                $query->with($with);
            }

            // Add a condition to check for the resource ID
            // Use the correct column name based on the model
            $columnName = $query->getModel()->getKeyName();
            $query->where($columnName, $id);

            // Add a condition to check ownership through relationships if any
            if ($with) {
                $query->whereHas($with, function ($query) use ($user) {
                    $query->where('User_ID', $user->User_ID);
                });
            } else {
                // Otherwise, directly check ownership
                $query->where('User_ID', $user->User_ID);
            }

            // Execute the query and fail if no result is found
            $query->firstOrFail();

            return true;
        } catch (\Exception $e) {
            // Return false if the resource is not found or not owned by the user
            return false;
        }
    }

    // Handle an incoming request. The $permissionPattern supports placeholders like {id} to be replaced with route parameters.
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permissionPattern
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permissionPattern)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized - not logged in'], 401);
        }

        // Define the mapping of input keys to their respective model configurations
        $ids = [
            'Organisation_ID' => ['route' => 'organisationId', 'model' => Organisation::class],
            'Team_ID' => ['route' => 'teamId', 'model' => Team::class, 'with' => 'organisation'],
            'Project_ID' => ['route' => 'projectId', 'model' => Project::class, 'with' => 'team.organisation'],
            'Backlog_ID' => ['route' => 'backlogId', 'model' => Backlog::class, 'with' => 'project.team.organisation'],
            'Status_ID' => ['route' => 'statusId', 'model' => Status::class, 'with' => 'backlog.project.team.organisation'],
            'Task_ID' => ['route' => 'taskId', 'model' => Task::class, 'with' => 'status.backlog.project.team.organisation'],
        ];

        // Loop through each ID configuration to check for ownership
        foreach ($ids as $inputKey => $config) {
            // Retrieve the ID from the request input or route parameters
            $id = $request->input($inputKey) ?? $request->route($config['route']);

            // If the ID exists and ownership is verified, proceed with the request
            if ($id && $this->checkOwnership($id, $config['model'], $config['with'] ?? null, $user)) {
                return $next($request);
            }
        }

        $permission = $this->resolvePermissionString($request, $permissionPattern);

        // Optionally, allow some global admin override permission key, e.g. "Modify Team Settings"
        $adminOverridePermission = "Modify Team Settings";

        // ✅ 2. Check role-based permission or override
        if ($user->hasPermission($permission, 0) || $user->hasPermission($adminOverridePermission, 0)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden - insufficient permissions or not organisation owner'], 403);
    }
}
