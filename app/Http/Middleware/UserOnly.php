<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use DateTime;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // return $next($request);
        try {
            // $user2 = JWTAuth::parseToken()->authenticate();
            // Use the 'api' guard explicitly
            $user = Auth::guard('api')->user(); // This will use the 'api' JWT guard to authenticate the user

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'UserOnly Unauthorized',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => 'UserOnly Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
        
        return $next($request);
    }
}
?>