<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

abstract class BaseController extends Controller
{
    /**
     * The model class associated with the controller.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * The relationships to eager load when fetching resources.
     *
     * @var array
     */
    protected array $with = [];

    /**
     * Define the validation rules for the resource.
     *
     * @return array The validation rules to be applied.
     */
    abstract protected function rules(): array;

    // Hook called after a resource is created.
    protected function afterStore(Model $resource): void
    {
        // Default: do nothing
    }

    // Hook called after a resource is updated.
    protected function afterUpdate(Model $resource): void
    {
        // Default: do nothing
    }

    // Hook called after a resource is deleted.
    protected function afterDestroy(Model $resource): void
    {
        // Default: do nothing
    }

    /**
     * Retrieve a listing of the resource with optional eager loading.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Try to get data from Cache
        $cacheKey = 'model:' . Str::snake(class_basename($this->modelClass)) . ':all';
        $cachedResources = Cache::get($cacheKey);

        if ($cachedResources) {
            $decodedResources = json_decode($cachedResources, true);
            return response()->json($decodedResources);
        }

        // If not in cache, fetch from DB
        // Retrieve all records of the model with defined eager-loaded relationships
        $resources = $this->modelClass::with($this->with)->get();

        // Store in Cache for 1 hour (3600 seconds)
        Cache::put($cacheKey, $resources->toJson(), 3600);

        // Return the records as a JSON response
        return response()->json($resources);
    }

    /**
     * Store a newly created resource in the database.
     *
     * @param Request $request The HTTP request containing input data.
     * @return JsonResponse The created resource as a JSON response.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the incoming request data based on the defined rules
        $validated = $request->validate($this->rules());

        // Create a new resource record in the database
        $resource = $this->modelClass::create($validated);

        // Invalidate the index cache
        Cache::forget('model:' . Str::snake(class_basename($this->modelClass)) . ':all');

        $this->afterStore($resource);

        // Return the newly created resource with a 201 (Created) status
        return response()->json($resource, 201);
    }

    /**
     * Display a specific resource by its ID.
     *
     * @param int $id The ID of the resource.
     * @return JsonResponse The requested resource or an error message if not found.
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = 'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $id;
        $cachedResource = Cache::get($cacheKey);

        if ($cachedResource) {
            $decodedResource = json_decode($cachedResource, true);
            return response()->json($decodedResource);
        }

        // Find the resource by ID and eager load defined relationships
        $resource = $this->modelClass::with($this->with)->find($id);

        // If the resource is not found, return a 404 response
        if (!$resource) {
            return response()->json(['message' => Str::singular(class_basename($this->modelClass)) . ' not found'], 404);
        }

        Cache::put($cacheKey, $resource->toJson(), 3600);

        // Return the found resource as a JSON response
        return response()->json($resource);
    }

    /**
     * Update an existing resource in the database.
     *
     * @param Request $request The HTTP request containing updated data.
     * @param int $id The ID of the resource to be updated.
     * @return JsonResponse The updated resource or an error message if not found.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Validate the incoming request data based on the defined rules
        $validated = $request->validate($this->rules());

        // Find the existing resource by ID
        $resource = $this->modelClass::find($id);

        // If the resource does not exist, return a 404 response
        if (!$resource) {
            return response()->json(['message' => Str::singular(class_basename($this->modelClass)) . ' not found'], 404);
        }

        // Update the resource with validated data
        $resource->update($validated);

        // Invalidate both the index and the specific resource cache
        $keys = [
            'model:' . Str::snake(class_basename($this->modelClass)) . ':all',
            'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $id
        ];

        Cache::deleteMultiple($keys);

        $this->afterUpdate($resource);

        // Return the updated resource as a JSON response
        return response()->json($resource);
    }

    /**
     * Remove a resource from the database.
     *
     * @param int $id The ID of the resource to be deleted.
     * @return JsonResponse A confirmation message or an error message if not found.
     */
    public function destroy(int $id): JsonResponse
    {
        // Find the resource by ID
        $resource = $this->modelClass::find($id);

        // If the resource does not exist, return a 404 response
        if (!$resource) {
            return response()->json(['message' => Str::singular(class_basename($this->modelClass)) . ' not found'], 404);
        }

        // Delete the resource from the database
        $resource->delete();

        // Invalidate both the index and the specific resource cache
        $keys = [
            'model:' . Str::snake(class_basename($this->modelClass)) . ':all',
            'model:' . Str::snake(class_basename($this->modelClass)) . ':' . $id
        ];

        Cache::deleteMultiple($keys);

        $this->afterDestroy($resource);

        // Return a success message as a JSON response
        return response()->json(['message' => Str::singular(class_basename($this->modelClass)) . ' deleted successfully.']);
    }
}
