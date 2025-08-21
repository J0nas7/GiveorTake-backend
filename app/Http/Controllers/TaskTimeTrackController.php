<?php

namespace App\Http\Controllers;

use App\Models\TaskTimeTrack;
use App\Models\Backlog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;

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

    /**
     * Clear Redis cache for a given task.
     *
     * @param int $taskId
     * @return void
     */
    private function clearTaskCache(int $taskId): void
    {
        // Redis::del("model:task:{$taskId}");
    }

    protected function afterStore($timeTrack): void
    {
        $this->clearTaskCache($timeTrack->Task_ID);
    }

    protected function afterUpdate($timeTrack): void
    {
        $this->clearTaskCache($timeTrack->Task_ID);
    }

    protected function afterDestroy($timeTrack): void
    {
        $this->clearTaskCache($timeTrack->Task_ID);
    }

    /**
     * Get time tracks by Task ID.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function getTaskTimeTracksByTask(int $taskId): JsonResponse
    {
        $timeTracks = TaskTimeTrack::with($this->with)
            ->where('Task_ID', $taskId)
            ->get();

        if ($timeTracks->isEmpty()) {
            return response()->json(['message' => 'No time tracks found for this task'], 404);
        }

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
        $latestTimeTracks = TaskTimeTrack::whereHas('task', function ($query) use ($backlogId) {
            $query->where('Backlog_ID', $backlogId);
        })
            ->with('task')
            ->orderBy('Time_Tracking_Start_Time', 'desc')
            ->get()
            ->unique('Task_ID')
            ->take(10);

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
            return response()->json(['message' => 'No time tracks found for the criterias'], 404);
        }

        return response()->json([
            "backlogIds" => $backlogIds,
            "userIds" => $userIds,
            "taskIds" => $taskIds,
            "data" => $allTimeTracks
        ]);
    }
}
