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
        'Task_Key',
        'Backlog_ID',
        'Team_ID',
        'Assigned_User_ID',
        'Task_Title',
        'Task_Description',
        'Status_ID',
        'Task_Due_Date',
        'Task_Hours_Spent',
    ];

    protected $dates = ['Task_CreatedAt', 'Task_UpdatedAt', 'Task_DeletedAt'];
    const CREATED_AT = 'Task_CreatedAt';
    const UPDATED_AT = 'Task_UpdatedAt';
    const DELETED_AT = 'Task_DeletedAt';

    // Relationships
    public function status()
    {
        return $this->belongsTo(Status::class, 'Status_ID');
    }

    public function backlog()
    {
        return $this->belongsTo(Backlog::class, 'Backlog_ID');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'Team_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'Assigned_User_ID');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'Task_ID');
    }

    public function timeTracks()
    {
        return $this->hasMany(TaskTimeTrack::class, 'Task_ID');
    }

    public function mediaFiles()
    {
        return $this->hasMany(TaskMediaFile::class, 'Task_ID');
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
