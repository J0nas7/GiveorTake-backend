<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = ActivityLog::class;

    /**
     * The relationships to eager load when fetching properties.
     *
     * @var array
     */
    protected array $with = [
        'user',
        'project',
    ];

    /**
     * Define the validation rules for properties.
     *
     * @return array The validation rules.
     */
    protected function rules(): array
    {
        $prefix = 'Log_';

        return [
            'User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Project_ID' => 'nullable|integer|exists:GT_Projects,Project_ID',
            $prefix . 'Action' => 'required|string|max:255',
            $prefix . 'Details' => 'nullable|json',
        ];
    }
}
