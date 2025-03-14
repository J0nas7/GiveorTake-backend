<?php

namespace App\Http\Controllers;

use App\Models\TaskTimeTrack;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TaskTimeTrackController extends Controller
{
    /**
     * Get all task time tracks for a specific task.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function getTaskTimeTracksByTask(int $taskId): JsonResponse
    {
        // Fetch all TaskTimeTrack records for the given Task_ID
        $timeTracks = TaskTimeTrack::where('Task_ID', $taskId)
            ->with('task') // Include the related task details (optional)
            ->get();

        // Check if any time tracks were found
        if ($timeTracks->isEmpty()) {
            return response()->json(['message' => 'No time tracks found for this task']);
        }

        // Return the time tracks as a JSON response
        return response()->json($timeTracks);
    }

    /**
     * Get all task time tracks for a specific project with optional filtering by start and end time.
     *
     * @param int $projectId
     * @param string|null $startTime (optional)
     * @param string|null $endTime (optional)
     * @return JsonResponse
     */
    public function getTaskTimeTracksByProject(int $projectId, Request $request): JsonResponse
    {
        // Ensure that startTime and endTime are provided as query parameters
        $startTime = $request->query('startTime');
        $endTime = $request->query('endTime');
        $userIds = $request->query('userIds');
        $taskIds = $request->query('taskIds');

        if (!$startTime || !$endTime) {
            return response()->json(['message' => 'Both startTime and endTime are required'], 400);
        }

        // Build the query to fetch TaskTimeTrack records for the given Project_ID
        $query = TaskTimeTrack::where('Project_ID', $projectId)
            ->with('task.project', 'user'); // Include the related task and user details

        // Filter by start and end time
        $query->where('Time_Tracking_Start_Time', '>=', $startTime)
            ->where('Time_Tracking_End_Time', '<=', $endTime);

        if ($userIds) {
            $userIdsArray = json_decode($userIds, true); // Decode JSON string into an array
            if (is_array($userIdsArray)) {
                $query->whereIn('User_ID', $userIdsArray);
            }
        }

        if ($taskIds) {
            $taskIdsArray = json_decode($taskIds, true); // Decode JSON string into an array
            if (is_array($taskIdsArray)) {
                $query->whereIn('Task_ID', $taskIdsArray);
            }
        }

        // Execute the query and get the results
        $timeTracks = $query->get();

        // Check if any time tracks were found
        if ($timeTracks->isEmpty()) {
            return response()->json(['message' => 'No time tracks found for the criterias']);
        }

        // Return the time tracks as a JSON response
        return response()->json($timeTracks);
    }

    public function getLatestUniqueTaskTimeTracksByProject(int $projectId): JsonResponse
    {
        // Get the 10 latest unique task time tracks for the given project, including task details
        $latestTimeTracks = TaskTimeTrack::whereHas('task', function ($query) use ($projectId) {
            $query->where('Project_ID', $projectId);
        })
            ->with('task') // Eager load related task data
            ->orderBy('Time_Tracking_Start_Time', 'desc') // Order by most recent start time
            ->get()
            ->unique('Task_ID') // Ensure unique Task_ID
            ->take(10); // Get the latest 10

        return response()->json($latestTimeTracks);
    }

    //// The rest of this TaskTimeTrackController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $timeTracks = TaskTimeTrack::all();
        return response()->json($timeTracks);
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
            'Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'Project_ID' => 'required|integer|exists:GT_Projects,Project_ID',
            'User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Comment_ID' => 'nullable|integer|exists:GT_Task_Comments,Comment_ID',
            'Time_Tracking_Start_Time' => 'required|date',
            'Time_Tracking_End_Time' => 'nullable|date|after_or_equal:Time_Tracking_Start_Time',
            'Time_Tracking_Duration' => 'nullable|integer|min:1',
            'Time_Tracking_Notes' => 'nullable|string',
        ]);

        // Find any active time track for the user (where Time_Tracking_End_Time is null)
        $activeTimeTrack = TaskTimeTrack::where('User_ID', $validated['User_ID'])
            ->whereNull('Time_Tracking_End_Time')
            ->first();

        if ($activeTimeTrack) {
            // End the active time track by setting the end time to now and calculating the duration
            $currentTime = now();
            $startTime = new \DateTime($activeTimeTrack->Time_Tracking_Start_Time);
            $duration = $startTime->diff($currentTime)->s + ($startTime->diff($currentTime)->i * 60) + ($startTime->diff($currentTime)->h * 3600);

            $activeTimeTrack->update([
                'Time_Tracking_End_Time' => $currentTime->toISOString(),
                'Time_Tracking_Duration' => $duration, // Duration in seconds
            ]);
        }

        // Create the new time tracking entry
        $timeTrack = TaskTimeTrack::create($validated);
        return response()->json($timeTrack, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $timeTrack = TaskTimeTrack::find($id);

        if (!$timeTrack) {
            return response()->json(['message' => 'Time tracking entry not found'], 404);
        }

        return response()->json($timeTrack);
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
            'Time_Tracking_End_Time' => 'nullable|date|after_or_equal:Time_Tracking_Start_Time',
            'Time_Tracking_Duration' => 'nullable|integer|min:1',
            'Time_Tracking_Notes' => 'nullable|string',
        ]);

        $timeTrack = TaskTimeTrack::find($id);

        if (!$timeTrack) {
            return response()->json(['message' => 'Time tracking entry not found'], 404);
        }

        $timeTrack->update($validated);
        return response()->json($timeTrack);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $timeTrack = TaskTimeTrack::find($id);

        if (!$timeTrack) {
            return response()->json(['message' => 'Time tracking entry not found'], 404);
        }

        $timeTrack->delete();
        return response()->json(['message' => 'Time tracking entry deleted successfully.']);
    }
}
