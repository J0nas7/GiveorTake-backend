<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organisation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'GT_Organisations'; // Table name
    protected $primaryKey = 'Organisation_ID'; // Primary key

    protected $fillable = [
        'User_ID',
        'Organisation_Name',
        'Organisation_Description',
    ];

    protected $dates = ['Organisation_CreatedAt', 'Organisation_UpdatedAt', 'Organisation_DeletedAt',];
    const CREATED_AT = 'Organisation_CreatedAt';
    const UPDATED_AT = 'Organisation_UpdatedAt';
    const DELETED_AT = 'Organisation_DeletedAt';

    // Relationships
    public function teams()
    {
        return $this->hasMany(Team::class, 'Organisation_ID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    /**
     * Get the factory for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\GTOrganisationFactory::new();
    }
}
