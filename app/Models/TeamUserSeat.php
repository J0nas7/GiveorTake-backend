<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamUserSeat extends Model
{
    use HasFactory;

    protected $table = 'GT_Team_User_Seats';

    protected $primaryKey = 'Seat_ID';

    protected $fillable = [
        'Team_ID',
        'User_ID',
        'Role_ID',
        'Seat_Status',
        'Seat_Role_Description',
        'Seat_Expiration',
        'Seat_CreatedAt',
        'Seat_UpdatedAt',
        'Seat_DeletedAt',
    ];

    protected $dates = [
        'Seat_Expiration',
        'Seat_CreatedAt',
        'Seat_UpdatedAt',
        'Seat_DeletedAt',
    ];

    const CREATED_AT = 'Seat_CreatedAt';
    const UPDATED_AT = 'Seat_UpdatedAt';
    const DELETED_AT = 'Seat_DeletedAt';

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class, 'Team_ID', 'Team_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'Role_ID', 'Role_ID');
    }
}
