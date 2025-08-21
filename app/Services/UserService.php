<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait UserService
{
    /**
     * Hash the given password using bcrypt.
     *
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email
     * @return \App\Models\User|null
     */
    public function findUserByEmail(string $email)
    {
        return User::where('User_Email', $email)->first();
    }
}
