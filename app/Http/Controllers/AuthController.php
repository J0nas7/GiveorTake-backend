<?php

namespace App\Http\Controllers;

use App\Models\Organisation;
use App\Models\Permission;
use App\Models\TaskTimeTrack;
use App\Models\TeamUserSeat;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use AuthService;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'forgotPassword', 'resetPassword', 'register']]);
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $result = $this->registerUser($request->all());

        if (isset($result['errors'])) {
            return response()->json(['errors' => $result['errors']], 400);
        }

        return response()->json($result, 201);
    }

    /**
     * Login a user and issue a JWT.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['User_Email', 'password']);
        $result = $this->authenticateUser($credentials);

        if (isset($result['error'])) {
            return response()->json([
                'error' => $result['error'],
                $credentials
            ], 401);
        }

        return response()->json($result, 200);
    }

    /**
     * Send password reset token.
     */
    public function forgotPassword(Request $request)
    {
        $result = $this->sendResetToken($request->all());

        if (isset($result['errors'])) {
            return response()->json(['errors' => $result['errors']], 400);
        }

        return response()->json($result, 200);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request)
    {
        $result = $this->resetPasswordWithToken($request->all());

        if (isset($result['errors'])) {
            return response()->json(['errors' => $result['errors']], 400);
        } elseif (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 401);
        }

        return response()->json($result, 200);
    }

    /**
     * This is useful for mobile apps to get a new token without re-entering credentials
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cloneToken(Request $request)
    {
        try {
            // Get user from incoming token
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json(['error' => 'Invalid or expired token'], 401);
            }

            // Create a new token for the same user (new device)
            $newToken = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'New token generated successfully',
                'data' => [
                    'user' => $user,
                    'accessToken' => $newToken,
                    // Optional: implement refresh tokens if you use them
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate token', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Logout the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->logoutUser();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    /**
     * Get details of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        // Check if the user is cached
        // $cacheKey = 'user:me:' . $user->User_ID;
        // $cachedData = Redis::get($cacheKey);

        // if ($cachedData) {
        //     return response()->json(json_decode($cachedData, true), 200);
        // }

        // Get all the teams where the user is assigned a seat
        $seats = TeamUserSeat::where('User_ID', $user->User_ID)
            ->with([
                'team.organisation',
                'team.projects',
                'role.permissions'
            ]) // Eager load the related Team, Organisation and Projects model, role and its permissions
            ->get();

        // Collect all unique permissions from all roles across seats
        $permissions = $seats
            ->pluck('role.permissions') // Get all permissions from each role
            ->flatten()
            ->unique('Permission_Key') // Ensure no duplicate permissions
            ->pluck('Permission_Key')  // Extract only the Permission_Key values
            ->values(); // Re-index the array

        $organisation = Organisation::with('teams.projects') // Eager load teams and projects
            ->where('User_ID', $user->User_ID)  // Check if the user is the owner of the organisation
            ->orWhereHas('teams.userSeats', function ($query) use ($user) {
                $query->where('User_ID', $user->User_ID);  // Check if the user has a seat in any team within the organisation
            })
            ->first();  // Get the first organisation that matches either condition

        $activeTimeTrack = TaskTimeTrack::with('task.backlog.project')
            ->where('User_ID', $user->User_ID)
            ->whereNull('Time_Tracking_End_Time') // This checks for an active timer (no end time)
            ->first();

        $responseData = [
            "success" => true,
            "message" => "Is logged in",
            "userData" => $user,
            "userSeats" => $seats,
            "permissions" => $permissions,
            "userOrganisation" => $organisation,
            "userActiveTimeTrack" => $activeTimeTrack
        ];

        // Cache for 15 minutes
        // Redis::setex($cacheKey, 900, json_encode($responseData));

        return response()->json($responseData, 200);
    }

    /**
     * Refreshes the JSON Web Token (JWT) for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request instance.
     * @return \Illuminate\Http\JsonResponse JSON response containing the new access token or an error message.
     */
    public function refreshJWT(Request $request)
    {
        try {
            // Get the current token from header
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }

            // Refresh the token
            $newToken = JWTAuth::refresh($token);

            return response()->json([
                'accessToken' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired and can no longer be refreshed'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }
}
