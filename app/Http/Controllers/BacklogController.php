<?php

namespace App\Http\Controllers;

use App\Models\Backlog;
use Illuminate\Http\JsonResponse;

class BacklogController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = Backlog::class;

    /**
     * The relationships to eager load when fetching properties.
     *
     * @var array
     */
    protected array $with = [
        'project',
        'team',
        'tasks'
    ];

    /**
     * Define the validation rules for properties.
     *
     * @return array The validation rules.
     */
    protected function rules(): array
    {
        return [
            'Project_ID' => 'nullable|integer|exists:GT_Projects,Project_ID',
            'Team_ID' => 'nullable|integer|exists:GT_Teams,Team_ID',
            'Backlog_Name' => 'required|string|max:255',
            'Backlog_Description' => 'nullable|string',
            'Backlog_IsPrimary' => 'boolean',
            'Backlog_StartDate' => 'nullable|date',
            'Backlog_EndDate' => 'nullable|date',
        ];
    }

    /**
     * Display a listing of backlogs based on Project ID.
     *
     * @param int $projectId
     * @return JsonResponse
     */
    public function getBacklogsByProject(int $projectId): JsonResponse
    {
        $backlogs = Backlog::where('Project_ID', $projectId)->get();

        if ($backlogs->isEmpty()) {
            return response()->json(['message' => 'No backlogs found for this project'], 404);
        }

        return response()->json($backlogs);
    }
}
?>