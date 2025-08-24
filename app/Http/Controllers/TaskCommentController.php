<?php

namespace App\Http\Controllers;

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

    /**
     * Clear the cache for a given task.
     *
     * @param int $taskId
     * @return void
     */
    private function clearTaskCache(int $taskId): void
    {
        Cache::forget("model:task:{$taskId}");
    }

    protected function afterStore($comment): void
    {
        $this->clearTaskCache($comment->Task_ID);
    }

    protected function afterUpdate($comment): void
    {
        $this->clearTaskCache($comment->Task_ID);
    }

    protected function afterDestroy($comment): void
    {
        $this->clearTaskCache($comment->Task_ID);
    }

    /**
     * Get comments by Task ID.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function getCommentsByTask(int $taskId): JsonResponse
    {
        $comments = TaskComment::with(['childrenComments', 'parentComment'])
            ->where('Task_ID', $taskId)
            ->get();

        if ($comments->isEmpty()) {
            return response()->json(['message' => 'No comments found for this task'], 404);
        }

        return response()->json($comments);
    }
}
