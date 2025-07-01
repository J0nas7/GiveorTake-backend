<?php

namespace App\Http\Middleware;

use App\Models\Organisation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * The $permissionPattern supports placeholders like {id} to be replaced with route parameters.
     *
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

        // Replace placeholders in $permissionPattern with route parameters
        preg_match_all('/\{(\w+)\}/', $permissionPattern, $matches);

        $permission = $permissionPattern;
        if (!empty($matches[1])) {
            foreach ($matches[1] as $param) {
                $value = $request->route($param) ?? $request->input($param);
                if (!$value) {
                    // If param not found, reject request
                    return response()->json(['message' => "Missing route or input parameter: {$param}"], 400);
                }
                $permission = str_replace("{{$param}}", $value, $permission);
            }
        }

        // Optionally, allow some global admin override permission key, e.g. "Modify Team Settings"
        $adminOverridePermission = "Modify Team Settings";

        // ✅ 1. Check role-based permission or override
        if ($user->hasPermission($permission) || $user->hasPermission($adminOverridePermission)) {
            return $next($request);
        }

        /*
        // ✅ 2. Check organisation ownership (if Organisation_ID exists in route or input)
        $organisationId = $request->route('Organisation_ID') ?? $request->input('Organisation_ID');

        if ($organisationId) {
            $organisation = Organisation::find($organisationId);
            if ($organisation && $organisation->User_ID === $user->User_ID) {
                return $next($request);
            }
        }*/

        // ✅ 2. Global organisation ownership check (owns *any* Organisation) // TODO
        $ownsOrganisation = Organisation::where('User_ID', $user->User_ID)->exists();

        if ($ownsOrganisation) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden - insufficient permissions or not organisation owner'], 403);
    }
}
