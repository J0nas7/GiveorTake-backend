<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    use UserService;

    /**
     * The Eloquent model class this controller works with.
     *
     * @var string
     */
    protected string $modelClass = User::class;

    /**
     * Optional relationships to eager load.
     *
     * @var array
     */
    protected array $with = [];

    /**
     * Validation rules for create/update operations.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'User_Status'    => 'required|integer',
            'User_Email'     => 'required|email|unique:GT_Users,User_Email',
            'User_Password'  => 'required|min:6',
            'User_FirstName' => 'required|string|max:100',
            'User_Surname'   => 'required|string|max:100',
            'User_ImageSrc'  => 'nullable|string|max:255',
        ];
    }

    /**
     * Hook after creating a user.
     * Hash the password before saving.
     */
    protected function afterStore($user): void
    {
        if ($user->User_Password) {
            $user->update(['User_Password' => $this->hashPassword($user->User_Password)]);
        }
    }

    /**
     * Hook after updating a user.
     * Hash password if it's being updated.
     */
    protected function afterUpdate($user): void
    {
        if (request()->filled('User_Password')) {
            $user->update(['User_Password' => $this->hashPassword(request()->input('User_Password'))]);
        }
    }

    /**
     * Custom endpoint: get user by email.
     */
    public function getUserByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = $this->findUserByEmail($request->input('email'));

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user, 200);
    }
}
