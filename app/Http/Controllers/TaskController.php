<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Backlog;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        'backlog.statuses',
        'timeTracks',
        'comments.user',
        'comments.childrenComments.user',
        'mediaFiles.user',
        'status',
        'subTasks.status',
        'parentTask',
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
            'Parent_Task_ID' => 'nullable|integer|exists:GT_Tasks,Task_ID',
            'Task_Description' => 'nullable|string',
            'Status_ID' => 'required|integer|exists:GT_Backlog_Statuses,Status_ID',
            'Assigned_User_ID' => 'nullable|integer|exists:GT_Users,User_ID',
            'Task_Due_Date' => 'nullable|date',
            'Task_Hours_Spent' => 'nullable|integer',
        ];
    }

    protected function clearTaskCache($task): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$task->Task_ID}"
        ];

        Cache::deleteMultiple($keys);

        if ($task->Backlog_ID) {
            Cache::forget("tasks:backlog:{$task->Backlog_ID}");
        }
    }

    protected function afterStore($task): void
    {
        $this->clearTaskCache($task);
    }

    protected function afterUpdate($task): void
    {
        $this->clearTaskCache($task);
    }

    protected function afterDestroy($task): void
    {
        $this->clearTaskCache($task);
    }

    /**
     * OVERRIDE OF BASECONTROLLER STORE()
     * Custom store with Task_Key generation.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the incoming request data using the defined rules
        $validated = $request->validate($this->rules());

        // Get the Project_ID for the given Backlog_ID
        $projectId = Backlog::where('Backlog_ID', $validated['Backlog_ID'])->value('Project_ID');

        // Get all backlogs for the project
        $backlogs = Backlog::where('Project_ID', $projectId)->get();

        $taskCount = 0;

        // Count all tasks (including soft-deleted) across all backlogs in the project
        foreach ($backlogs as $backlog) {
            $taskCount += Task::withTrashed()->where('Backlog_ID', $backlog['Backlog_ID'])->count();
        }

        // Generate the next Task_Key for the project
        $taskKey = $taskCount + 1;

        // Create the new task with the generated Task_Key
        $task = Task::create(array_merge($validated, ['Task_Key' => $taskKey]));

        // Eager load the task relationships
        $task->load($this->with);

        // Clear relevant cache after storing the task
        $this->afterStore($task);

        // Return the created task as JSON with 201 status
        return response()->json($task, 201);
    }

    /**
     * Get tasks by Backlog ID.
     *
     * @param Backlog $backlog
     * @return JsonResponse
     */
    public function getTasksByBacklog(Backlog $backlog): JsonResponse
    {
        $tasks = Task::with($this->with)
            ->where('Backlog_ID', $backlog->Backlog_ID)
            ->whereNull('Parent_Task_ID')
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
        // Get the authenticated user
        $user = Auth::guard('api')->user();
        if (!$user) {
            // Return 401 if not authenticated
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        // Find the project by key and check user access via organisation or user seat
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
            // Return 404 if project not found or user has no access
            return response()->json(['message' => 'Project not found'], 404);
        }

        // Find the task by Task_Key and Backlog_IDs belonging to the project
        $task = Task::with($this->with)
            ->where('Task_Key', $taskKey)
            ->where('Backlog_ID', function ($query) use ($project) {
                $query->select('Backlog_ID')
                    ->from('GT_Backlogs')
                    ->where('Project_ID', $project->Project_ID);
            })
            ->first();

        if (!$task) {
            // Return 404 if task not found
            return response()->json(['message' => 'Task not found'], 404);
        }

        // Return the found task with relationships
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
            'tasks.*.Parent_Task_ID' => 'nullable|integer|exists:GT_Tasks,Task_ID',
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
                Cache::forget("tasks:backlog:{$task->Backlog_ID}");
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
                Cache::forget("tasks:backlog:{$task->Backlog_ID}");
            }
        }

        return response()->json([
            'success' => count($tasks) . ' task(s) deleted successfully.'
        ]);
    }
}
