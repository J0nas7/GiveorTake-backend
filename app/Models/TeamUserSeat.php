<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamUserSeat extends Model
{
    use HasFactory;

    protected $table = 'GT_Team_User_Seats';

    protected $primaryKey = 'Seat_ID';

    // Defining the field names with prefix
    protected $fillable = [
        'Team_ID',
        'User_ID',
        'Seat_Role',
        'Seat_Status',
        'Seat_Role_Description',
        'Seat_Permissions',
        'Seat_CreatedAt',
        'Seat_UpdatedAt',
        'Seat_DeletedAt',
    ];

    // Defining the date fields
    protected $dates = ['Seat_CreatedAt', 'Seat_UpdatedAt', 'Seat_DeletedAt'];

    // Setting the custom names for the created_at, updated_at, and deleted_at fields
    const CREATED_AT = 'Seat_CreatedAt';
    const UPDATED_AT = 'Seat_UpdatedAt';
    const DELETED_AT = 'Seat_DeletedAt';

    // Cast the Seat_Permissions to array for JSON storage
    protected $casts = [
        'Seat_Permissions' => 'array', // Ensure the permissions field is cast to array
    ];

    // Relationships: A seat belongs to a team and a user
    public function team()
    {
        return $this->belongsTo(Team::class, 'Team_ID', 'Team_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID', 'User_ID');
    }
}
?>