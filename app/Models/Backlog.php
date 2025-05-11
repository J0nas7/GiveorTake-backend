<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Backlog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Backlogs'; // Table name
    protected $primaryKey = 'Backlog_ID'; // Primary key

    protected $fillable = [
        'Project_ID',
        'Team_ID',
        'Backlog_Name',
        'Backlog_Description',
        'Backlog_StartDate',
        'Backlog_EndDate',
    ];

    protected $dates = [
        'Backlog_CreatedAt',
        'Backlog_UpdatedAt',
        'Backlog_DeletedAt',
        'Backlog_StartDate',
        'Backlog_EndDate',
    ];

    const CREATED_AT = 'Backlog_CreatedAt';
    const UPDATED_AT = 'Backlog_UpdatedAt';
    const DELETED_AT = 'Backlog_DeletedAt';

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class, 'Project_ID');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'Team_ID');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'Backlog_ID');
    }
}
?>