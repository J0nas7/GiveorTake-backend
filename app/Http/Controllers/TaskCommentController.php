<?php

namespace App\Http\Controllers;

use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TaskCommentController extends Controller
{
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

    //// The rest of this TaskCommentController is RESTful API methods ////

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $comments = TaskComment::all();
        return response()->json($comments);
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
            'Comment_Text' => 'required|string',
            'Parent_Comment_ID' => 'nullable|integer|exists:GT_Task_Comments,Comment_ID',
        ]);

        $comment = TaskComment::create($validated);
        return response()->json($comment, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $comment = TaskComment::with(['childrenComments', 'user'])->find($id);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        return response()->json($comment);
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
            'Comment_Text' => 'required|string',
        ]);

        $comment = TaskComment::find($id);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $comment->update($validated);
        return response()->json($comment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = TaskComment::find($id);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted successfully.']);
    }
}
