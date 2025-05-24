<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'GT_Roles';

    protected $primaryKey = 'Role_ID';

    protected $fillable = [
        'Role_Name',
        'Role_Description',
        'Role_CreatedAt',
        'Role_UpdatedAt',
        'Role_DeletedAt',
    ];

    protected $dates = [
        'Role_CreatedAt',
        'Role_UpdatedAt',
        'Role_DeletedAt',
    ];

    const CREATED_AT = 'Role_CreatedAt';
    const UPDATED_AT = 'Role_UpdatedAt';
    const DELETED_AT = 'Role_DeletedAt';

    // A Role belongs to many TeamUserSeats
    public function teamUserSeats()
    {
        return $this->hasMany(TeamUserSeat::class, 'Role_ID', 'Role_ID');
    }

    // A Role has many permissions (via GT_Role_Permissions)
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'GT_Role_Permissions',
            'Role_ID',
            'Permission_ID'
        );
    }
}
