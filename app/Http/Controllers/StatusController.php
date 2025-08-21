<?php

namespace App\Http\Controllers;

use App\Models\Backlog;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class StatusController extends BaseController
{
    /**
     * The model class associated with this controller.
     *
     * @var string
     */
    protected string $modelClass = Status::class;

    /**
     * The relationships to eager load when fetching properties.
     *
     * @var array
     */
    protected array $with = [
        'backlog',
        'tasks'
    ];

    /**
     * Define the validation rules for statuses.
     *
     * @return array The validation rules.
     */
    protected function rules(): array
    {
        return [
            'Status_Name' => 'required|string|max:100',
            'Status_Order' => 'nullable|integer|min:0',
            'Status_Is_Default' => 'boolean',
            'Status_Is_Closed' => 'boolean',
            'Status_Color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'Backlog_ID' => 'required|exists:GT_Backlogs,Backlog_ID'
        ];
    }

    private function clearBacklogCache(int $backlogId): void
    {
        // Redis::del("model:backlog:{$backlogId}");
    }

    // OVERRIDE of the BaseController methods
    public function destroy(int $id): JsonResponse
    {
        // Find the status to be deleted
        /** @var Status|null $statusToDelete */
        $statusToDelete = $this->modelClass::find($id);

        if (!$statusToDelete) {
            return response()->json([
                'message' => Str::singular(class_basename($this->modelClass)) . ' not found'
            ], 404);
        }

        // Find the fallback/default status in the same backlog
        $defaultStatus = Status::where('Backlog_ID', $statusToDelete->Backlog_ID)
            ->where('Status_Is_Default', true)
            ->first();

        // If no default status exists, abort
        if (!$defaultStatus) {
            return response()->json([
                'message' => 'Default status not found in the same backlog. Cannot delete this status.'
            ], 400);
        }

        // Reassign all tasks with the status to be deleted to the default status
        Task::where('Status_ID', $statusToDelete->Status_ID)
            ->update(['Status_ID' => $defaultStatus->Status_ID]);

        // Delete the status
        $statusToDelete->delete();

        $this->refreshOrder($statusToDelete->Backlog_ID);

        $this->clearBacklogCache($statusToDelete->Backlog_ID);

        return response()->json([
            'message' => 'Status deleted successfully, and associated tasks reassigned to the default status.'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Validate the incoming request data
        $validated = $request->validate($this->rules());

        // Create the new status
        /** @var \App\Models\Status $newStatus */
        $newStatus = $this->modelClass::create($validated);

        $this->refreshOrder($newStatus->Backlog_ID);

        $this->clearBacklogCache($newStatus->Backlog_ID);

        // Return the newly created resource
        return response()->json($newStatus, 201);
    }

    // CUSTOM methods for the StatusController
    /**
     * Adjust the Status_Order of a given Status by moving it up or down within its backlog.
     *
     * @param  int    $id        The ID of the status to move.
     * @param  string $direction The direction to move: "up" or "down".
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveOrder(int $statusId, Request $request): JsonResponse
    {
        $direction = $request->input('direction');

        if (!in_array($direction, ['up', 'down'])) {
            return response()->json(['message' => 'Invalid direction provided.'], 422);
        }

        // Fetch the current status
        /** @var \App\Models\Status $status */
        $status = \App\Models\Status::find($statusId);

        if (!$status) {
            return response()->json(['message' => 'Status not found'], 404);
        }

        // Prevent movement for default or closed statuses
        if ($status->Status_Is_Default || $status->Status_Is_Closed) {
            return response()->json(['message' => 'Default or Closed statuses cannot be moved'], 422);
        }

        $currentOrder = $status->Status_Order;
        $backlogId = $status->Backlog_ID;

        // Determine direction
        $targetOrder = match ($direction) {
            'up' => $currentOrder - 1,
            'down' => $currentOrder + 1,
            default => null,
        };

        if (!$targetOrder || $targetOrder < 1) {
            return response()->json(['message' => 'Invalid move direction or position'], 400);
        }

        // Find the other status to swap with
        $swapWith = \App\Models\Status::where('Backlog_ID', $backlogId)
            ->where('Status_Order', $targetOrder)
            ->first();

        if (!$swapWith) {
            return response()->json(['message' => 'No status found to swap with'], 404);
        }

        // Swap orders
        [$status->Status_Order, $swapWith->Status_Order] = [$swapWith->Status_Order, $status->Status_Order];

        $status->save();
        $swapWith->save();

        $this->refreshOrder($status->Backlog_ID);

        return response()->json(['message' => 'Status order updated successfully']);
    }

    /**
     * Refresh the Status_Order for all statuses within the same Backlog_ID.
     * Ensures the default status is first, closed status is last, and others are in between.
     *
     * @param  int  $id  The ID of one of the statuses in the backlog to refresh.
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshOrder(int $backlogId)
    {
        // Get all statuses for this backlog, grouped by role
        $statuses = \App\Models\Status::where('Backlog_ID', $backlogId)->get();

        $default = $statuses->firstWhere('Status_Is_Default', true);
        $closed = $statuses->firstWhere('Status_Is_Closed', true);

        $others = $statuses
            ->filter(fn($s) => !$s->Status_Is_Default && !$s->Status_Is_Closed)
            ->sortBy('Status_Order')
            ->values();

        $order = 1;

        // Assign default status first
        if ($default) {
            $default->Status_Order = $order++;
            $default->save();
        }

        // Assign normal statuses
        foreach ($others as $status) {
            $status->Status_Order = $order++;
            $status->save();
        }

        // Assign closed status last
        if ($closed) {
            $closed->Status_Order = $order;
            $closed->save();
        }

        $this->clearBacklogCache($backlogId);
    }

    /**
     * Assign the given status as the default for its backlog.
     *
     * This method:
     * - Prevents assigning a closed status as default.
     * - Assigns the given status Status_Is_Default = true and Status_Order = 1.
     * - If another status was previously the default, it:
     *   - Sets Status_Is_Default = false.
     *   - Assigns it the old Status_Order of the new default.
     *
     * @param  int  $id  The ID of the status to assign as default.
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignDefault(int $statusId): JsonResponse
    {
        /** @var \App\Models\Status $status */
        $status = \App\Models\Status::find($statusId);

        if (!$status) {
            return response()->json(['message' => 'Status not found'], 404);
        }

        if ($status->Status_Is_Closed) {
            return response()->json(['message' => 'Closed statuses cannot be set as default'], 422);
        }

        $backlogId = $status->Backlog_ID;
        $currentOrder = $status->Status_Order;

        // Find the current default status (excluding this one)
        $existingDefault = \App\Models\Status::where('Backlog_ID', $backlogId)
            ->where('Status_Is_Default', true)
            ->where('Status_ID', '!=', $status->Status_ID)
            ->first();

        // Swap order if another default exists
        if ($existingDefault) {
            $existingDefault->Status_Is_Default = false;
            $existingDefault->Status_Order = $currentOrder;
            $existingDefault->save();
        }

        // Set this status as default with order = 1
        $status->Status_Is_Default = true;
        $status->Status_Order = 1;
        $status->save();

        $this->refreshOrder($status->Backlog_ID);

        $this->clearBacklogCache($backlogId);

        return response()->json(['message' => 'Default status assigned successfully.']);
    }

    /**
     * Assign the given status as the closed status for its backlog.
     *
     * This method:
     * - Prevents assigning a default status as closed.
     * - Assigns the given status Status_Is_Closed = true and Status_Order = the highest order + 1.
     * - If another status was previously the closed status, it:
     *   - Sets Status_Is_Closed = false.
     *   - Assigns it the old Status_Order of the new closed status.
     *
     * @param  int  $id  The ID of the status to assign as closed.
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignClosed(int $statusId): JsonResponse
    {
        /** @var \App\Models\Status $status */
        $status = \App\Models\Status::find($statusId);

        if (!$status) {
            return response()->json(['message' => 'Status not found'], 404);
        }

        if ($status->Status_Is_Default) {
            return response()->json(['message' => 'Default statuses cannot be set as closed'], 422);
        }

        $backlogId = $status->Backlog_ID;
        $currentOrder = $status->Status_Order;

        // Find the current closed status (excluding this one)
        $existingClosed = \App\Models\Status::where('Backlog_ID', $backlogId)
            ->where('Status_Is_Closed', true)
            ->where('Status_ID', '!=', $status->Status_ID)
            ->first();

        // Swap order if another closed status exists
        if ($existingClosed) {
            $existingClosed->Status_Is_Closed = false;
            $existingClosed->Status_Order = $currentOrder;
            $existingClosed->save();
        }

        // Set this status as closed with the highest order + 1
        $highestOrder = \App\Models\Status::where('Backlog_ID', $backlogId)->max('Status_Order');
        $status->Status_Is_Closed = true;
        $status->Status_Order = $highestOrder + 1;
        $status->save();

        $this->refreshOrder($status->Backlog_ID);

        $this->clearBacklogCache($backlogId);

        return response()->json(['message' => 'Closed status assigned successfully.']);
    }
}
