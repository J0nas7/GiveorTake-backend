<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TaskCommentController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = TaskComment::class;

    /**
     * The relationships to eager load when fetching properties.
     *
     * @var array
     */
    protected array $with = [
        'childrenComments',
        'parentComment',
        'user',
    ];

    /**
     * Validation rules for task comments.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Comment_Text' => 'required|string',
            'Parent_Comment_ID' => 'nullable|integer|exists:GT_Task_Comments,Comment_ID',
        ];
    }

    protected function clearTaskCache($comment): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$comment->Comment_ID}"
        ];

        Cache::deleteMultiple($keys);

        if ($comment->Task_ID) {
            $keys = [
                "model:task:{$comment->Task_ID}",
                "comments:task:{$comment->Task_ID}"
            ];

            Cache::deleteMultiple($keys);
        }
    }

    protected function afterStore($comment): void
    {
        $this->clearTaskCache($comment);
    }

    protected function afterUpdate($comment): void
    {
        $this->clearTaskCache($comment);
    }

    protected function afterDestroy($comment): void
    {
        $this->clearTaskCache($comment);
    }

    /**
     * Get comments by Task ID.
     *
     * @param Task $task
     * @return JsonResponse
     */
    public function getCommentsByTask(Task $task): JsonResponse
    {
        $cacheKey = "comments:task:{$task->Task_ID}";
        $cachedComments = Cache::get($cacheKey);

        if ($cachedComments) {
            $decodedComments = json_decode($cachedComments, true);
            return response()->json($decodedComments);
        }

        $comments = TaskComment::with(['childrenComments', 'parentComment'])
            ->where('Task_ID', $task->Task_ID)
            ->get();

        if ($comments->isEmpty()) {
            return response()->json(['message' => 'No comments found for this task'], 404);
        }

        Cache::put($cacheKey, $comments->toJson(), 3600);

        return response()->json($comments);
    }
}
