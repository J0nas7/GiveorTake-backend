<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskMediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Task_Media_Files';
    protected $primaryKey = 'Media_ID';

    protected $fillable = [
        'Task_ID',
        'Uploaded_By_User_ID',
        'Media_File_Name',
        'Media_File_Path',
        'Media_File_Type',
    ];

    protected $dates = ['Media_CreatedAt', 'Media_UpdatedAt', 'Media_DeletedAt'];
    const CREATED_AT = 'Media_CreatedAt';
    const UPDATED_AT = 'Media_UpdatedAt';
    const DELETED_AT = 'Media_DeletedAt';

    public function task()
    {
        return $this->belongsTo(Task::class, 'Task_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'Uploaded_By_User_ID');
    }
}
?>