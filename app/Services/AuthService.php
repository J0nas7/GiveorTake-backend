<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function registerUser(array $data)
    {
        // Validate the input fields
        $validator = Validator::make($data, [
            'acceptTerms'       => 'required|accepted', // accepted: allows values like yes, on, 1, or true.
            'userEmail'       => 'required|email|unique:GT_Users,User_Email',
            'userPassword'    => 'required|min:6|confirmed',
            'userFirstname'   => 'required|string|max:100', // Validate first name
            'userSurname'     => 'required|string|max:100', // Validate surname
            'User_ImageSrc'    => 'nullable|string|max:255', // Optional image source
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        // Create a new user with validated data
        $user = User::create([
            'User_Email'       => $data['userEmail'],
            'User_Password'    => Hash::make($data['userPassword']),
            'User_Status'      => 1,
            'User_FirstName'   => $data['userFirstname'], // Include first name
            'User_Surname'     => $data['userSurname'],   // Include surname
            'User_ImageSrc'    => $data['User_ImageSrc'] ?? null, // Optional field
        ]);

        return ['success' => true, 'message' => 'User was created.'];
    }

    /**
     * Authenticate a user and generate a JWT.
     *
     * @param array $credentials
     * @return array
     */
    public function authenticateUser(array $credentials)
    {
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return ['error' => 'Invalid email or password'];
        }

        return [
            'success' => true,
            'message' => 'Login was successful',
            'data' => [
                'user' => Auth::guard('api')->user(),
                'accessToken' => $token
            ]
        ];
        // Manually query the database to find the user
        /*$user = User::where('User_Email', $credentials['User_Email'])->first();

        if ($user && Hash::check($credentials['User_Password'], $user->User_Password)) {
            // Generate token and return it
            $token = JWTAuth::fromUser($user);
            // Authenticate the user for the current request
            Auth::guard('api')->login($user);

            return [
                'success' => true,
                'message' => 'Login was successful',
                'data' => [
                    'user' => Auth::guard('api')->user(),
                    'accessToken' => $token
                ]
            ];
        }

        return ['error' => 'Invalid email or password'];*/
    }

    public function sendResetToken(array $data)
    {
        $validator = Validator::make($data, [
            'User_Email' => 'required|email|exists:GT_Users,User_Email',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        // Generate 6-character alphanumeric uppercase token
        $token = strtoupper(Str::random(6));

        // Update user with the reset token
        $user = User::where('User_Email', $data['User_Email'])->first();
        $user->User_Remember_Token = $token;
        $user->save();

        // Send email
        Mail::raw("Your password reset token is: $token", function ($message) use ($user) {
            $message->to($user->User_Email)
                ->subject('Password Reset Token');
        });

        return ['success' => true, 'message' => 'Password reset token sent.'];
    }

    public function resetPasswordWithToken(array $data)
    {
        $validator = Validator::make($data, [
            'User_Email' => 'required|email|exists:GT_Users,User_Email',
            'User_Remember_Token' => 'required|string|size:6',
            'New_User_Password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        $user = User::where('User_Email', $data['User_Email'])
            ->where('User_Remember_Token', $data['User_Remember_Token'])
            ->first();

        if (!$user) {
            return ['error' => 'Invalid token or email.'];
        }

        // Update the password
        $user->User_Password = Hash::make($data['New_User_Password']);
        $user->User_Remember_Token = null; // Clear token
        $user->save();

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    /**
     * Logout the authenticated user.
     *
     * @return bool
     */
    public function logoutUser()
    {
        Auth::guard('api')->logout();
        return true;
    }

    /**
     * Get the authenticated user.
     *
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        $authUser = Auth::guard('api')->user();
        return $authUser;
    }
}
