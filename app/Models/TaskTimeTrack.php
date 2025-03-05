<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTimeTrack extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Task_Time_Trackings';
    protected $primaryKey = 'Time_Tracking_ID';

    protected $fillable = [
        'Task_ID',
        'User_ID',
        'Comment_ID',
        'Time_Tracking_Start_Time',
        'Time_Tracking_End_Time',
        'Time_Tracking_Duration',
        'Time_Tracking_Notes',
    ];

    protected $dates = ['Time_Tracking_CreatedAt', 'Time_Tracking_UpdatedAt', 'Time_Tracking_DeletedAt'];
    const CREATED_AT = 'Time_Tracking_CreatedAt';
    const UPDATED_AT = 'Time_Tracking_UpdatedAt';
    const DELETED_AT = 'Time_Tracking_DeletedAt';

    public function task()
    {
        return $this->belongsTo(Task::class, 'Task_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    public function comment()
    {
        return $this->belongsTo(TaskComment::class, 'Comment_ID');
    }
}
