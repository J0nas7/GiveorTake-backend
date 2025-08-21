<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Activity_Logs'; // Table name
    protected $primaryKey = 'Log_ID'; // Primary key

    protected $fillable = [
        'User_ID',
        'Project_ID',
        'Log_Action',
        'Log_Details',
    ];

    protected $dates = ['Log_CreatedAt', 'Log_UpdatedAt', 'Log_DeletedAt'];
    const CREATED_AT = 'Log_CreatedAt';
    const UPDATED_AT = 'Log_UpdatedAt';
    const DELETED_AT = 'Log_DeletedAt';

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID', 'User_ID');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'Project_ID', 'Project_ID');
    }
}
