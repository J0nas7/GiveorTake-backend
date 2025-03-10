<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks based on Project ID.
     *
     * @param int $projectId
     * @return JsonResponse
     */
    public function getTasksByProject(int $projectId): JsonResponse
    {
        $tasks = Task::with('project.team.userSeats.user', 'timeTracks', 'comments', 'mediaFiles') // Eager load project etc., comments and mediaFiles
            ->where('Project_ID', $projectId) // Filter by Project_ID
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json(['message' => 'No tasks found for this project'], 404);
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
        $tasks = Task::with('project', 'timeTracks', 'comments', 'mediaFiles')->get(); // Eager load project, comments and mediaFiles
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
        $validated = $request->validate([
            'Project_ID' => 'required|integer|exists:GT_Projects,Project_ID', // Ensure the project exists
            'Task_Title' => 'required|string|max:255',
            'Task_Description' => 'nullable|string',
            'Task_Status' => 'required|string',
            'Task_Number' => 'required|integer',
            // 'Task_Start_Date' => 'required|date',
            'Task_Due_Date' => 'nullable|date',
        ]);

        $task = Task::create($validated); // Store the new task
        return response()->json($task, 201); // Return created task as JSON with HTTP status 201
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with('project.team.userSeats.user', 'timeTracks', 'comments', 'mediaFiles') // Eager load project etc., comments and mediaFiles
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
            'Project_ID' => 'required|integer|exists:GT_Projects,Project_ID', // Ensure the project exists
            'Task_Title' => 'required|string|max:255',
            'Task_Description' => 'nullable|string',
            'Task_Status' => 'required|string',
            'Task_Due_Date' => 'nullable|date',
        ]);

        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404); // Return 404 if not found
        }

        $task->update($validated); // Update the task
        return response()->json($task); // Return the updated task as JSON
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
}
