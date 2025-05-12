<?php

namespace App\Http\Controllers;

use App\Models\Backlog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function finishBacklog(Request $request, int $backlogId): JsonResponse
    {
        $backlog = Backlog::find($backlogId);

        if (!$backlog) {
            return response()->json(['message' => 'Backlog not found'], 404);
        }

        $request->validate([
            'moveAction' => 'required|in:move-to-primary,move-to-new,move-to-existing',
            'Project_ID' => 'nullable|integer|exists:GT_Projects,Project_ID',
            'Team_ID' => 'nullable|integer|exists:GT_Teams,Team_ID',
            'Backlog_Name' => ($request->moveAction === 'move-to-new' ? 'required' : 'nullable') . '|string|max:255',
            'Backlog_Description' => 'nullable|string',
            'Backlog_IsPrimary' => 'boolean',
            'Backlog_StartDate' => 'nullable|date',
            'Backlog_EndDate' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $unfinishedTasks = $backlog->tasks()->where('Task_Status', '!=', 'Done')->get();

            $targetBacklogId = null;

            if ($request->moveAction === 'move-to-primary') {
                $primaryBacklog = Backlog::where('Project_ID', $backlog->Project_ID)
                    ->where('Backlog_IsPrimary', true)
                    ->first();

                if (!$primaryBacklog) {
                    return response()->json(['message' => 'Primary backlog not found for this project'], 400);
                }

                $targetBacklogId = $primaryBacklog->Backlog_ID;
            } else if ($request->moveAction === 'move-to-new') {
                $newBacklog = Backlog::create([
                    'Project_ID' => $backlog->Project_ID,
                    'Team_ID' => $backlog->Team_ID,
                    'Backlog_Name' => $request->Backlog_Name,
                    'Backlog_IsPrimary' => false,
                    'Backlog_StartDate' => now(),
                ]);

                $targetBacklogId = $newBacklog->Backlog_ID;
            } else if ($request->moveAction === 'move-to-existing') {
                $targetBacklogId = $request->Backlog_ID;
            }

            foreach ($unfinishedTasks as $task) {
                $task->Backlog_ID = $targetBacklogId;
                $task->save();
            }

            // $backlog->Backlog_EndDate = now();
            $backlog->delete();

            DB::commit();

            return response()->json([
                'message' => 'Backlog finished and tasks moved successfully',
                'moved_tasks_count' => $unfinishedTasks->count(),
                'target_backlog_id' => $targetBacklogId
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to finish backlog', 'error' => $e->getMessage()], 500);
        }
    }
}
