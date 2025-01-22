<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

trait UserService
{
    /**
     * Hash the given password using bcrypt.
     *
     * @param string $password
     * @return string
     */
    public function hashPassword($password)
    {
        return Hash::make($password);
    }

    /**
     * Get all users.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllUsers()
    {
        return User::all();
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findUserById($id)
    {
        return User::find($id);
    }

    /**
     * Validate and create a new user.
     *
     * @param array $data
     * @return array
     */
    public function createUser(array $data)
    {
        // Validate input fields
        $validator = Validator::make($data, [
            'User_Status'      => 'required|integer',
            'User_Email'       => 'required|email|unique:GT_Users,User_Email',
            'User_Password'    => 'required|min:6',
            'User_FirstName'   => 'required|string|max:100', // Validate first name
            'User_Surname'     => 'required|string|max:100', // Validate surname
            'User_ImageSrc'    => 'nullable|string|max:255',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        // Create a new user with validated data
        $user = User::create([
            'User_Status'      => $data['User_Status'],
            'User_Email'       => $data['User_Email'],
            'User_Password'    => $this->hashPassword($data['User_Password']),
            'User_FirstName'   => $data['User_FirstName'], // Include first name
            'User_Surname'     => $data['User_Surname'],   // Include surname
            'User_ImageSrc'    => $data['User_ImageSrc'] ?? null, // Optional field
        ]);

        return ['user' => $user];
    }

    /**
     * Validate and update an existing user.
     *
     * @param array $data
     * @param User $user
     * @return array
     */
    public function updateUser(array $data, User $user)
    {
        $validator = Validator::make($data, [
            'User_Status' => 'nullable|integer',
            'User_Email' => 'nullable|email|unique:GT_Users,User_Email,' . $user->User_ID . ',User_ID',
            'User_Password' => 'nullable|min:6',
            'User_ImageSrc' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ['errors' => $validator->errors()];
        }

        $updateData = array_filter([
            'User_Status' => $data['User_Status'] ?? null,
            'User_Email' => $data['User_Email'] ?? null,
            'User_Password' => isset($data['User_Password']) ? $this->hashPassword($data['User_Password']) : null,
            'User_ImageSrc' => $data['User_ImageSrc'] ?? null,
        ]);

        $user->update($updateData);

        return ['user' => $user];
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return bool
     */
    public function deleteUser(User $user)
    {
        return $user->delete();
    }
}
