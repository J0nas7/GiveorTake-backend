<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Projects'; // Table name
    protected $primaryKey = 'Project_ID'; // Primary key

    protected $fillable = [
        'Team_ID',
        'Project_Name',
        'Project_Key',
        'Project_Description',
        'Project_Status',
        'Project_Start_Date',
        'Project_End_Date',
    ];

    protected $dates = ['Project_CreatedAt', 'Project_UpdatedAt', 'Project_DeletedAt',];
    const CREATED_AT = 'Project_CreatedAt';
    const UPDATED_AT = 'Project_UpdatedAt';
    const DELETED_AT = 'Project_DeletedAt';

    // Relationships
    public function team()
    {
        return $this->belongsTo(Team::class, 'Team_ID');
    }

    public function backlogs()
    {
        return $this->hasMany(Backlog::class, 'Project_ID');
    }

    /**
     * Get the factory for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\GTProjectFactory::new();
    }
}
