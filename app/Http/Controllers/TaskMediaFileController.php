<?php

namespace App\Http\Controllers;

use App\Models\TaskMediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TaskMediaFileController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = TaskMediaFile::class;

    /**
     * The relationships to eager load when fetching media files.
     *
     * @var array
     */
    protected array $with = [
        'task',
        'user',
    ];

    /**
     * Validation rules for task media files.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'Uploaded_By_User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Media_File' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ];
    }

    private function clearTaskCache(int $taskId): void
    {
        Cache::forget("model:task:{$taskId}");
    }

    /**
     * Hook after deleting a media file.
     *
     * @param TaskMediaFile $mediaFile
     * @return void
     */
    protected function afterDestroy($mediaFile): void
    {
        // Delete physical file
        if (Storage::disk('public')->exists($mediaFile->Media_File_Path)) {
            Storage::disk('public')->delete($mediaFile->Media_File_Path);
            $this->clearTaskCache($mediaFile->Task_ID);
        }
    }

    /**
     * Store a newly created task media file in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the incoming request
        $validated = $request->validate([
            'Task_ID' => 'required|integer|exists:GT_Tasks,Task_ID',
            'Uploaded_By_User_ID' => 'required|integer|exists:GT_Users,User_ID',
            'Media_File' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Validate the file type (JPG, PNG, PDF) and max size (10MB)
        ]);

        // Check if the file exists in the request
        if ($request->hasFile('Media_File')) {
            // Get the uploaded file
            $file = $request->file('Media_File');

            // Generate a unique file name
            $fileName = time() . '-' . $file->getClientOriginalName();

            // Store the file in the 'public' disk (you can change the disk as per your requirement)
            $filePath = $file->storeAs('task_media', $fileName, 'public');

            // Prepare data to store in the database
            $mediaFileData = [
                'Task_ID' => $validated['Task_ID'],
                'Uploaded_By_User_ID' => $validated['Uploaded_By_User_ID'],
                'Media_File_Name' => $fileName,
                'Media_File_Path' => $filePath,
                'Media_File_Type' => $file->getClientOriginalExtension(), // Store the file extension (JPG, PNG, PDF)
            ];

            // Create a new TaskMediaFile record
            $mediaFile = TaskMediaFile::create($mediaFileData);

            // Return a success response with the media file data
            return response()->json($mediaFile, 201);
        } else {
            return response()->json(['error' => 'No media file uploaded.'], 400);
        }
    }

    //// CUSTOM METHODS ////

    /**
     * Get media files by Task ID.
     *
     * @param int $taskId
     * @return JsonResponse
     */
    public function getMediaFilesByTask(int $taskId): JsonResponse
    {
        $mediaFiles = TaskMediaFile::with($this->with)
            ->where('Task_ID', $taskId)
            ->get();

        if ($mediaFiles->isEmpty()) {
            return response()->json(['message' => 'No media files found for this task'], 404);
        }

        return response()->json($mediaFiles);
    }
}
