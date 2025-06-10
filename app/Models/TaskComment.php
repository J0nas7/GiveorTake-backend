<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Task_Comments';
    protected $primaryKey = 'Comment_ID';

    protected $fillable = [
        'Task_ID',
        'User_ID',
        'Parent_Comment_ID',
        'Time_Tracking_ID',
        'Comment_Text',
    ];

    protected $dates = ['Comment_CreatedAt', 'Comment_UpdatedAt', 'Comment_DeletedAt'];
    const CREATED_AT = 'Comment_CreatedAt';
    const UPDATED_AT = 'Comment_UpdatedAt';
    const DELETED_AT = 'Comment_DeletedAt';

    public function parentComment()
    {
        return $this->belongsTo(TaskComment::class, 'Parent_Comment_ID', 'Comment_ID');
    }

    public function childrenComments()
    {
        return $this->hasMany(TaskComment::class, 'Parent_Comment_ID', 'Comment_ID');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'Task_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    public function timeTracking()
    {
        return $this->belongsTo(TaskTimeTrack::class, 'Time_Tracking_ID');
    }
}
