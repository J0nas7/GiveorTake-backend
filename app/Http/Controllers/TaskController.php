<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Backlog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks based on Backlog ID.
     *
     * @param int $backlogId
     * @return JsonResponse
     */
    public function getTasksByBacklog(int $backlogId): JsonResponse
    {
        $tasks = Task::with('backlog.project.team.userSeats.user', 'timeTracks', 'comments', 'mediaFiles') // Eager load backlog etc., comments and mediaFiles
            ->where('Backlog_ID', $backlogId) // Filter by Backlog_ID
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found for this backlog'], 404);
        }

        return response()->json($tasks);
    }

    //// The rest of this ProjectController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $tasks = Task::with('backlog.project', 'timeTracks', 'comments', 'mediaFiles')->get(); // Eager load project, comments and mediaFiles
        return response()->json($tasks); // Return tasks as JSON
    }

    /**
     * Show the form for creating a new resource.
     * This method is not typically used in an API.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Use the POST method to create a task.'], 405); // Method Not Allowed
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request (without Task_Key)
        $validated = $request->validate([
            'Backlog_ID' => 'required|integer|exists:GT_Backlogs,Backlog_ID',
            'Task_Title' => 'required|string|max:255',
            'Task_Description' => 'nullable|string',
            'Status_ID' => 'required|integer|exists:GT_Backlog_Statuses,Status_ID',
            'Assigned_User_ID' => 'nullable|integer',
            'Task_Due_Date' => 'nullable|date',
        ]);

        $projectId = Backlog::where('Backlog_ID', $validated['Backlog_ID'])->value('Project_ID');
        $backlogs = Backlog::where('Project_ID', $projectId)->get();
        $taskCount = 0;
        
        foreach ($backlogs as $backlog) {
            // Count all tasks including soft deleted ones
            $taskCount += Task::withTrashed()->where('Backlog_ID', $backlog['Backlog_ID'])->count();
        }

        // Generate Task_Key based on count
        $taskKey = $taskCount + 1;

        // Create task with generated Task_Key
        $task = Task::create(array_merge($validated, ['Task_Key' => $taskKey]));

        return response()->json($task, 201); // Return created task as JSON with HTTP status 201
    }

    /**
     * Get specific task by project key and task key
     * 
     * @param string projectKey
     * @param int taskKey
     * @return JsonResponse
     */
    public function getTaskByKeys(string $projectKey, int $taskKey): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $userId = $user->User_ID;

        // Find the project with the matching key
        $project = Project::where('Project_Key', $projectKey)
            ->whereHas('team', function ($query) use ($userId) {
                $query->whereHas('organisation', function ($subQuery) use ($userId) {
                    $subQuery->where('User_ID', $userId); // User is the owner of the organisation
                })->orWhereHas('userSeats', function ($subQuery) use ($userId) {
                    $subQuery->where('User_ID', $userId); // User has a seat in the team
                });
            })
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404); // Return 404 if not found
        }

        // Find the task with the matching key
        $task = Task::with([
            'backlog.project.team.userSeats.user',
            'timeTracks',
            'comments.user',
            'mediaFiles.user'
        ])
            ->where('Task_Key', $taskKey)
            // ->where('Project_ID', $project->Project_ID) // Ensure task belongs to the project
            ->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404); // Return 404 if not found
        }

        return response()->json($task); // Return the task as JSON
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with('backlog.project.team.userSeats.user', 'timeTracks', 'comments', 'mediaFiles') // Eager load project etc., comments and mediaFiles
            ->find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404); // Return 404 if not found
        }

        return response()->json($task); // Return the task as JSON
    }

    /**
     * Show the form for editing the specified resource.
     * This method is not typically used in an API.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function edit(int $id): JsonResponse
    {
        return response()->json(['message' => 'Use the PUT or PATCH method to edit a task.'], 405); // Method Not Allowed
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'Backlog_ID' => 'required|integer|exists:GT_Backlogs,Backlog_ID', // Ensure the backlog exists
            'Task_Title' => 'required|string|max:255',
            'Task_Description' => 'nullable|string',
            'Status_ID' => 'required|integer|exists:GT_Backlog_Statuses,Status_ID',
            'Task_Due_Date' => 'nullable|date',
            'Assigned_User_ID' => 'nullable|integer',
        ]);

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404); // Return 404 if not found
        }

        $task->update($validated); // Update the task
        return response()->json($task); // Return the updated task as JSON
    }

    /**
     * Bulk update multiple tasks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'tasks.*.Backlog_ID' => 'nullable|integer|exists:GT_Backlogs,Backlog_ID',
            'tasks.*.Status_ID' => 'required|integer|exists:GT_Backlog_Statuses,Status_ID',
            'tasks.*.Task_Due_Date' => 'nullable|date',
            'tasks.*.Assigned_User_ID' => 'nullable|integer|exists:GT_Users,User_ID',
        ]);

        $updatedTasks = [];

        foreach ($validated['tasks'] as $taskData) {
            $task = Task::find($taskData['Task_ID']);

            if ($task) {
                $task->update([
                    'Backlog_ID' => $taskData['Backlog_ID'] ?? $task->Backlog_ID,
                    'Status_ID' => $taskData['Status_ID'] ?? $task->Status_ID,
                    'Task_Due_Date' => $taskData['Task_Due_Date'] ?? $task->Task_Due_Date,
                    'Assigned_User_ID' => $taskData['Assigned_User_ID'] ?? $task->Assigned_User_ID,
                ]);

                $updatedTasks[] = $task;
            }
        }

        return response()->json([
            'message' => count($updatedTasks) . ' task(s) updated successfully.',
            'updated_tasks' => $updatedTasks,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404); // Return 404 if not found
        }

        $task->delete(); // Soft delete the task
        return response()->json(['message' => 'Task deleted successfully.']); // Return success message
    }

    /**
     * Remove multiple resources from storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        // Get task_ids from the request body (expecting a JSON string)
        $taskIds = $request->input('task_ids');

        // Decode the JSON string into a PHP array
        $taskIds = json_decode($taskIds, true);

        if (!is_array($taskIds) || empty($taskIds)) {
            return response()->json(['message' => 'No task IDs provided.'], 400);
        }

        // Find tasks that exist
        $tasks = Task::whereIn('Task_ID', $taskIds)->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No matching tasks found.'], 404);
        }

        // Perform soft delete
        Task::whereIn('Task_ID', $taskIds)->delete();

        return response()->json([
            'success' => count($tasks) . ' task(s) deleted successfully.'
        ]);
    }
}
