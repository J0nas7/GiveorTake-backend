<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskMediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
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

    protected function clearTaskCache($media): void
    {
        $modelName = Str::snake(class_basename($this->modelClass));
        $keys = [
            "model:{$modelName}:all",
            "model:{$modelName}:{$media->Media_ID}"
        ];

        Cache::deleteMultiple($keys);

        if ($media->Task_ID) {
            $keys = [
                "model:task:{$media->Task_ID}",
                "media:task:{$media->Task_ID}"
            ];

            Cache::deleteMultiple($keys);
        }
    }

    protected function afterStore($media): void
    {
        $this->clearTaskCache($media);
    }

    protected function afterUpdate($media): void
    {
        $this->clearTaskCache($media);
    }

    protected function afterDestroy($media): void
    {
        // Delete physical file
        if (Storage::disk('public')->exists($media->Media_File_Path)) {
            Storage::disk('public')->delete($media->Media_File_Path);
        }

        $this->clearTaskCache($media);
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

            $this->afterStore($mediaFile);

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
     * @param Task $task
     * @return JsonResponse
     */
    public function getMediaFilesByTask(Task $task): JsonResponse
    {
        $cacheKey = "media:task:{$task->Task_ID}";
        $cachedFiles = Cache::get($cacheKey);

        if ($cachedFiles) {
            $decodedFiles = json_decode($cachedFiles, true);
            return response()->json($decodedFiles);
        }

        $mediaFiles = TaskMediaFile::with($this->with)
            ->where('Task_ID', $task->Task_ID)
            ->get();

        if ($mediaFiles->isEmpty()) {
            return response()->json(['message' => 'No media files found for this task'], 404);
        }

        Cache::put($cacheKey, $mediaFiles->toJson(), 3600);

        return response()->json($mediaFiles);
    }
}
