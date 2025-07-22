<?php

namespace App\Http\Middleware;

use App\Models\Organisation;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    protected function resolvePermissionString(Request $request, string $pattern): string
    {
        preg_match_all('/\{(\w+)\}/', $pattern, $matches);

        foreach ($matches[1] ?? [] as $param) {
            $value = $request->route($param) ?? $request->input($param);
            if (!$value) {
                abort(response()->json(['message' => "Missing route or input parameter: {$param}"], 400));
            }
            $pattern = str_replace("{{$param}}", $value, $pattern);
        }

        return $pattern;
    }

    /**
     * Handle an incoming request.
     * The $permissionPattern supports placeholders like {id} to be replaced with route parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permissionPattern
     * @param  string  $context
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permissionPattern, string $context)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized - not logged in'], 401);
        }

        // 1. Check if the request contains a contextual ID
        $organisationId = $request->route('organisationId');
        $teamId = $request->input('Team_ID') ?? $request->route('team_id') ?? $request->route('teamId');

        if ($organisationId) {
            try {
                $organisation = Organisation::where('Organisation_ID', $organisationId)
                    ->where('User_ID', $user->User_ID)
                    ->firstOrFail();

                return $next($request);
            } catch (\Exception $e) {
                // ownership not verified — fall back to permission pattern check
            }
        } else if ($teamId) {
            try {
                $team = Team::with('organisation')
                    ->where('Team_ID', $teamId)
                    ->whereHas('organisation', function ($query) use ($user) {
                        $query->where('User_ID', $user->User_ID);
                    })
                    ->firstOrFail();

                return $next($request);
            } catch (\Exception $e) {
                // ownership not verified — fall back to permission pattern check
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
