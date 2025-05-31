<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    // use HasFactory<\Database\Factories\UserFactory>
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'GT_Users';
    protected $primaryKey = 'User_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'User_Status',
        'User_Email',
        'User_Password',
        'User_FirstName',
        'User_Surname',
        'User_ImageSrc',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'User_Password',
        'User_Remember_Token',
    ];

    protected $dates = ['User_CreatedAt', 'User_UpdatedAt', 'User_DeletedAt',];
    const CREATED_AT = 'User_CreatedAt';
    const UPDATED_AT = 'User_UpdatedAt';
    const DELETED_AT = 'User_DeletedAt';

    public function roles()
    {
        return $this->hasMany(Role::class, 'User_ID');
    }

    /**
     * Check if the user has a specific permission based on their assigned roles.
     *
     * Iterates through all roles assigned to the user and checks if any of the roles
     * contain the specified permission key.
     *
     * @param string $permissionKey The key of the permission to check for.
     * @return bool Returns true if the user has the specified permission, false otherwise.
     */
    public function hasPermission(string $permissionKey): bool
    {
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->Permission_Key === $permissionKey) {
                    return true;
                }
            }
        }
        return false;
    }

    // The database field that should be returned on Eloquent's request
    public function getAuthPassword()
    {
        return $this->User_Password;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the factory for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\GTUserFactory::new();
    }
}
