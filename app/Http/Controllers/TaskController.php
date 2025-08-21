<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Backlog;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class TaskController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = Task::class;

    /**
     * The relationships to eager load when fetching tasks.
     *
     * @var array
     */
    protected array $with = [
        'backlog.project.team.userSeats.user',
        'timeTracks',
        'comments.user',
        'comments.childrenComments.user',
        'mediaFiles.user',
        'status'
    ];

    /**
     * Validation rules for tasks.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'Backlog_ID' => 'required|integer|exists:GT_Backlogs,Backlog_ID',
            'Task_Title' => 'required|string|max:255',
            'Task_Description' => 'nullable|string',
            'Status_ID' => 'required|integer|exists:GT_Backlog_Statuses,Status_ID',
            'Assigned_User_ID' => 'nullable|integer|exists:GT_Users,User_ID',
            'Task_Due_Date' => 'nullable|date',
            'Task_Hours_Spent' => 'nullable|integer',
        ];
    }

    /**
     * Hook called after a resource is created.
     */
    protected function afterStore($task): void
    {
        // Clear index cache
        // Redis::del("model:tasksByBacklog:{$task->Backlog_ID}");
    }

    /**
     * Hook called after a resource is updated.
     */
    protected function afterUpdate($task): void
    {
        // Redis::del("model:tasksByBacklog:{$task->Backlog_ID}");
    }

    /**
     * Hook called after a resource is deleted.
     */
    protected function afterDestroy($task): void
    {
        // Redis::del("model:tasksByBacklog:{$task->Backlog_ID}");
    }

    /**
     * Custom store with Task_Key generation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $projectId = Backlog::where('Backlog_ID', $validated['Backlog_ID'])->value('Project_ID');
        $backlogs = Backlog::where('Project_ID', $projectId)->get();
        $taskCount = 0;

        foreach ($backlogs as $backlog) {
            $taskCount += Task::withTrashed()->where('Backlog_ID', $backlog['Backlog_ID'])->count();
        }

        $taskKey = $taskCount + 1;

        $task = Task::create(array_merge($validated, ['Task_Key' => $taskKey]));

        $this->afterStore($task);

        return response()->json($task, 201);
    }

    /**
     * Get tasks by Backlog ID.
     */
    public function getTasksByBacklog(int $backlogId): JsonResponse
    {
        $tasks = Task::with($this->with)
            ->where('Backlog_ID', $backlogId)
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found for this backlog'], 404);
        }

        return response()->json($tasks);
    }

    /**
     * Get a task by project key and task key (with auth + access checks).
     */
    public function getTaskByKeys(string $projectKey, int $taskKey): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $project = Project::where('Project_Key', $projectKey)
            ->whereHas('team', function ($query) use ($user) {
                $query->whereHas('organisation', function ($subQuery) use ($user) {
                    $subQuery->where('User_ID', $user->User_ID);
                })->orWhereHas('userSeats', function ($subQuery) use ($user) {
                    $subQuery->where('User_ID', $user->User_ID);
                });
            })
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $task = Task::with($this->with)
            ->where('Task_Key', $taskKey)
            ->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json($task);
    }

    /**
     * Bulk update tasks.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'tasks.*.Backlog_ID' => 'nullable|integer|exists:GT_Backlogs,Backlog_ID',
            'tasks.*.Status_ID' => 'nullable|integer|exists:GT_Backlog_Statuses,Status_ID',
            'tasks.*.Task_Due_Date' => 'nullable|date',
            'tasks.*.Assigned_User_ID' => 'nullable|integer|exists:GT_Users,User_ID',
        ]);

        $updatedTasks = [];

        foreach ($validated['tasks'] as $taskData) {
            $task = Task::find($taskData['Task_ID']);
            if ($task) {
                $task->update(array_filter($taskData));
                $updatedTasks[] = $task;
                // Redis::del("model:tasksByBacklog:{$task->Backlog_ID}");
            }
        }

        return response()->json([
            'message' => count($updatedTasks) . ' task(s) updated successfully.',
            'updated_tasks' => $updatedTasks,
        ]);
    }

    /**
     * Bulk destroy tasks.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $taskIds = json_decode($request->input('task_ids'), true);

        if (!is_array($taskIds) || empty($taskIds)) {
            return response()->json(['message' => 'No task IDs provided.'], 400);
        }

        $tasks = Task::whereIn('Task_ID', $taskIds)->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No matching tasks found.'], 404);
        }

        Task::whereIn('Task_ID', $taskIds)->delete();

        foreach ($taskIds as $id) {
            $task = Task::withTrashed()->find($id);
            if ($task) {
                // Redis::del("model:tasksByBacklog:{$task->Backlog_ID}");
            }
        }

        return response()->json([
            'success' => count($tasks) . ' task(s) deleted successfully.'
        ]);
    }
}
