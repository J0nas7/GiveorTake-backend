<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'GT_Permissions';

    protected $primaryKey = 'Permission_ID';

    protected $fillable = [
        'Permission_Key',
        'Permission_Description',
        'Permission_CreatedAt',
        'Permission_UpdatedAt',
        'Permission_DeletedAt',
    ];

    protected $dates = [
        'Permission_CreatedAt',
        'Permission_UpdatedAt',
        'Permission_DeletedAt',
    ];

    const CREATED_AT = 'Permission_CreatedAt';
    const UPDATED_AT = 'Permission_UpdatedAt';
    const DELETED_AT = 'Permission_DeletedAt';

    // A Permission belongs to many Roles via the GT_Role_Permissions pivot table
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'GT_Role_Permissions',
            'Permission_ID',
            'Role_ID'
        );
    }
}
