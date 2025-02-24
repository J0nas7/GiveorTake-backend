<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Tasks'; // Table name
    protected $primaryKey = 'Task_ID'; // Primary key

    protected $fillable = [
        'Project_ID',
        'Team_ID',
        'Assigned_User_ID',
        'Task_Title',
        'Task_Description',
        'Task_Status',
        'Task_Due_Date',
    ];

    protected $dates = ['Task_CreatedAt', 'Task_UpdatedAt', 'Task_DeletedAt'];
    const CREATED_AT = 'Task_CreatedAt';
    const UPDATED_AT = 'Task_UpdatedAt';
    const DELETED_AT = 'Task_DeletedAt';

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class, 'Project_ID');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'Team_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'Assigned_User_ID');
    }

    /**
     * Get the factory for the model.
     *
     * @return \Database\Factories\GTTaskFactory
     */
    protected static function newFactory()
    {
        return \Database\Factories\GTTaskFactory::new();
    }
}
?>