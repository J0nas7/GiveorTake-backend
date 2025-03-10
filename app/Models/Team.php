<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Teams'; // Table name
    protected $primaryKey = 'Team_ID'; // Primary key

    protected $fillable = [
        'Organisation_ID',
        'Team_Name',
        'Team_Description',
    ];

    protected $dates = ['Team_CreatedAt', 'Team_UpdatedAt', 'Team_DeletedAt'];
    const CREATED_AT = 'Team_CreatedAt';
    const UPDATED_AT = 'Team_UpdatedAt';
    const DELETED_AT = 'Team_DeletedAt';

    // Relationships
    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'Organisation_ID');
    }

    public function userSeats()
    {
        return $this->hasMany(TeamUserSeat::class, 'Team_ID');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'Team_ID');
    }
    
    public function tasks()
    {
        return $this->hasMany(Task::class, 'Team_ID');
    }

    /**
     * Get the factory for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\GTTeamFactory::new();
    }
}
?>