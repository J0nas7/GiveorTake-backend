<?php

namespace App\Http\Controllers;

use App\Models\TaskTimeTrack;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TaskTimeTrackController extends Controller
{
    /**
     * Get total time spent by task ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getTotalTimeByTask(int $taskId): JsonResponse
    {
        $totalTime = TaskTimeTrack::where('Task_ID', $taskId)->sum('Time_Tracking_Duration');
        return response()->json(['Task_ID' => $taskId, 'Total_Time' => $totalTime]);
    }

    /**
     * Get total time spent by task ID and user ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getTotalTimeByUserOnTask(int $taskId, int $userId): JsonResponse
    {
        $totalTime = TaskTimeTrack::where('Task_ID', $taskId)->where('User_ID', $userId)->sum('Time_Tracking_Duration');
        return response()->json(['Task_ID' => $taskId, 'User_ID' => $userId, 'Total_Time' => $totalTime]);
    }

    /**
     * Get active timers by user ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getActiveTimersByUserId(int $userId): JsonResponse
    {
        $activeTimers = TaskTimeTrack::where('User_ID', $userId)->whereNull('Time_Tracking_End_Time')->get();
        return response()->json($activeTimers);
    }

    /**
     * Get the active time track for a specific task and user.
     *
     * @param int $taskId
     * @param int $userId
     * @return JsonResponse
     */
    public function getActiveTimeTrackForTask(int $taskId, int $userId): JsonResponse
    {
        $activeTimeTrack = TaskTimeTrack::where('Task_ID', $taskId)
            ->where('User_ID', $userId)
            ->whereNull('Time_Tracking_End_Time') // This checks for an active timer (no end time)
            ->first();

        if (!$activeTimeTrack) {
            return response()->json(['message' => 'No active time track found for this task and user.'], 404);
        }

        return response()->json($activeTimeTrack);
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
