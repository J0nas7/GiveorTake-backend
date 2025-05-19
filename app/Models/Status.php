<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $table = 'GT_Backlog_Statuses';
    protected $primaryKey = 'Status_ID';

    protected $fillable = [
        'Backlog_ID',
        'Status_Name',
        'Status_Key',
        'Status_Order',
        'Status_Is_Default',
        'Status_Is_Closed',
        'Status_Color',
    ];

    protected $casts = [
        'Status_Is_Default' => 'boolean',
        'Status_Is_Closed' => 'boolean',
    ];

    protected $dates = ['Status_CreatedAt', 'Status_UpdatedAt', 'Status_DeletedAt'];
    const CREATED_AT = 'Status_CreatedAt';
    const UPDATED_AT = 'Status_UpdatedAt';
    const DELETED_AT = 'Status_DeletedAt';

    public function backlog()
    {
        return $this->belongsTo(Backlog::class, 'Backlog_ID');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'Status_ID');
    }
}
?>