<?php

namespace App\Http\Controllers;

use App\Models\TaskTimeTrack;
use App\Models\Backlog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class TaskTimeTrackController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = TaskTimeTrack::class;

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected array $with = [
        'task',
        'user',
    ];

    /**
     * Validation rules for TaskTimeTrack.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'Backlog_ID' => 'required|integer|exists:GT_Backlogs,Backlog_ID',
            'User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Time_Tracking_Start_Time' => 'required|date',
            'Time_Tracking_End_Time' => 'nullable|date|after_or_equal:Time_Tracking_Start_Time',
            'Time_Tracking_Duration' => 'nullable|integer|min:1',
            'Time_Tracking_Notes' => 'nullable|string',
        ];
    }

    protected function clearTaskCache($timeTrack): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$timeTrack->Time_Tracking_ID}"
        ];

        Cache::deleteMultiple($keys);

        if ($timeTrack->Task_ID) {
            $keys = [
                "model:task:{$timeTrack->Task_ID}",
                "timetracks:task:{$timeTrack->Task_ID}"
            ];

            Cache::deleteMultiple($keys);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();
        $cacheKey = "latestTimeTracks:user:{$user->User_ID}";
        Cache::forget($cacheKey);

        // Clear all cache entries with the 'task_time_tracks' tag
        Cache::tags('task_time_tracks')->flush();
    }

    protected function afterStore($timeTrack): void
    {
        $this->clearTaskCache($timeTrack);
    }

    protected function afterUpdate($timeTrack): void
    {
        $this->clearTaskCache($timeTrack);
    }

    protected function afterDestroy($timeTrack): void
    {
        $this->clearTaskCache($timeTrack);
    }

    /**
     * Get time tracks by Task ID.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function getTaskTimeTracksByTask(int $taskId): JsonResponse
    {
        $cacheKey = "timetracks:task:{$taskId}";
        $cachedTimeTracks = Cache::get($cacheKey);

        if ($cachedTimeTracks) {
            $decodedTimeTracks = json_decode($cachedTimeTracks, true);
            return response()->json($decodedTimeTracks);
        }

        $timeTracks = TaskTimeTrack::with($this->with)
            ->where('Task_ID', $taskId)
            ->get();

        if ($timeTracks->isEmpty()) {
            return response()->json(['message' => 'No time tracks found for this task'], 404);
        }

        Cache::put($cacheKey, $timeTracks->toJson(), 3600);

        return response()->json($timeTracks);
    }

    /**
     * Get the latest 10 unique task time tracks for a backlog.
     *
     * @param int $backlogId
     * @return JsonResponse
     */
    public function getLatestUniqueTaskTimeTracksByBacklog(int $backlogId): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        $cacheKey = "latestTimeTracks:user:{$user->User_ID}";
        $cachedLatestTimeTracks = Cache::get($cacheKey);

        if ($cachedLatestTimeTracks) {
            $decodedLatestTimeTracks = json_decode($cachedLatestTimeTracks, true);
            return response()->json($decodedLatestTimeTracks);
        }

        $latestTimeTracks = TaskTimeTrack::where('User_ID', $user->User_ID)
            ->whereHas('task', function ($query) use ($backlogId) {
                $query->where('Backlog_ID', $backlogId);
            })
            ->with('task')
            ->orderBy('Time_Tracking_Start_Time', 'desc')
            ->get()
            ->unique('Task_ID')
            ->take(10);

        Cache::put($cacheKey, $latestTimeTracks->toJson(), 3600);

        return response()->json($latestTimeTracks);
    }

    /**
     * Get time tracks by Project ID with optional filtering.
     *
     * @param int $projectId
     * @param Request $request
     * @return JsonResponse
     */
    public function getTaskTimeTracksByProject(int $projectId, Request $request): JsonResponse
    {
        $startTime = $request->query('startTime');
        $endTime = $request->query('endTime');

        if (!$startTime || !$endTime) {
            return response()->json(['message' => 'Both startTime and endTime are required'], 400);
        }

        // Generate a flexible cache key
        $cacheKey = $this->generateCacheKey($projectId, $request);

        // Try to get the cached data
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            return response()->json($cachedData, 200);
        }

        $backlogIds = $request->query('backlogIds');
        $userIds = $request->query('userIds');
        $taskIds = $request->query('taskIds');

        $allTimeTracks = [];
        $backlogs = Backlog::where('Project_ID', $projectId)->get();

        foreach ($backlogs as $backlog) {
            $query = TaskTimeTrack::where('Backlog_ID', $backlog->Backlog_ID)
                ->with('task.backlog.project', 'user')
                ->where('Time_Tracking_Start_Time', '>=', $startTime)
                ->where('Time_Tracking_End_Time', '<=', $endTime);

            if ($backlogIds) {
                $backlogIdsArray = json_decode($backlogIds, true);
                if (is_array($backlogIdsArray) && count($backlogIdsArray)) {
                    $query->whereHas('task', fn($q) => $q->whereIn('Backlog_ID', $backlogIdsArray));
                }
            }

            if ($userIds) {
                $userIdsArray = json_decode($userIds, true);
                if (is_array($userIdsArray) && count($userIdsArray)) {
                    $query->whereIn('User_ID', $userIdsArray);
                }
            }

            if ($taskIds) {
                $taskIdsArray = json_decode($taskIds, true);
                if (is_array($taskIdsArray) && count($taskIdsArray)) {
                    $query->whereIn('Task_ID', $taskIdsArray);
                }
            }

            $allTimeTracks = array_merge($allTimeTracks, $query->get()->toArray());
        }

        if (!count($allTimeTracks)) {
            return response()->json(['message' => 'No time tracks found for the criteria'], 404);
        }

        // Prepare the response data
        $responseData = [
            "backlogIds" => $backlogIds,
            "userIds" => $userIds,
            "taskIds" => $taskIds,
            "data" => $allTimeTracks
        ];

        // Cache the response data for 1 hour
        Cache::tags(['task_time_tracks'])->put($cacheKey, $responseData, now()->addHours(1));

        return response()->json($responseData);
    }

    /**
     * Generate a flexible cache key based on the request parameters.
     *
     * @param int $projectId
     * @param Request $request
     * @return string
     */
    protected function generateCacheKey(int $projectId, Request $request): string
    {
        $params = [
            'projectId' => $projectId,
            'startTime' => $request->query('startTime'),
            'endTime' => $request->query('endTime'),
            'backlogIds' => $request->query('backlogIds', 'none'),
            'userIds' => $request->query('userIds', 'none'),
            'taskIds' => $request->query('taskIds', 'none'),
        ];

        // Generate a unique cache key using the parameters
        return 'task_time_tracks:' . md5(json_encode($params));
    }
}
