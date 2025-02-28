<?php

namespace App\Http\Controllers;

use App\Models\TaskMediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TaskMediaFileController extends Controller
{
    /**
     * Get media files by Task ID.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function getMediaFilesByTask(int $taskId): JsonResponse
    {
        $mediaFiles = TaskMediaFile::where('Task_ID', $taskId)->get();

        if ($mediaFiles->isEmpty()) {
            return response()->json(['message' => 'No media files found for this task'], 404);
        }

        return response()->json($mediaFiles);
    }

    //// The rest of this TaskMediaFileController is RESTful API methods ////

    /**
     * Display a listing of the task media files.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $mediaFiles = TaskMediaFile::all();
        return response()->json($mediaFiles);
    }

    /**
     * Store a newly created task media file in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'Uploaded_By_User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Media_File_Name' => 'required|string',
            'Media_File_Path' => 'required|string',
            'Media_File_Type' => 'required|string',
        ]);

        $mediaFile = TaskMediaFile::create($validated);
        return response()->json($mediaFile, 201);
    }

    /**
     * Display the specified task media file.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $mediaFile = TaskMediaFile::find($id);

        if (!$mediaFile) {
            return response()->json(['message' => 'Task media file not found'], 404);
        }

        return response()->json($mediaFile);
    }

    /**
     * Update the specified task media file in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'Media_File_Name' => 'required|string',
            'Media_File_Path' => 'required|string',
            'Media_File_Type' => 'required|string',
        ]);

        $mediaFile = TaskMediaFile::find($id);

        if (!$mediaFile) {
            return response()->json(['message' => 'Task media file not found'], 404);
        }

        $mediaFile->update($validated);
        return response()->json($mediaFile);
    }

    /**
     * Remove the specified task media file from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $mediaFile = TaskMediaFile::find($id);

        if (!$mediaFile) {
            return response()->json(['message' => 'Task media file not found'], 404);
        }

        $mediaFile->delete();
        return response()->json(['message' => 'Task media file deleted successfully.']);
    }
}
?>