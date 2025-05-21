<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // Fetch all statuses with the same Backlog_ID
        $statuses = \App\Models\Status::where('Backlog_ID', $newStatus->Backlog_ID)
            ->get();

        // Separate statuses into categories
        $defaultStatus = $statuses->firstWhere('Status_Is_Default', true);
        $closedStatus = $statuses->firstWhere('Status_Is_Closed', true);

        $middleStatuses = $statuses->filter(function ($status) use ($defaultStatus, $closedStatus) {
            return $status->Status_ID !== optional($defaultStatus)->Status_ID &&
                $status->Status_ID !== optional($closedStatus)->Status_ID;
        });

        // Rebuild the new ordered list
        $orderedStatuses = collect();

        if ($defaultStatus) {
            $orderedStatuses->push($defaultStatus);
        }

        $orderedStatuses = $orderedStatuses->merge(
            $middleStatuses->sortBy('Status_ID') // or Status_Name, or any other desired order
        );

        if ($closedStatus && $closedStatus->Status_ID !== optional($defaultStatus)->Status_ID) {
            $orderedStatuses->push($closedStatus);
        }

        // Reassign Status_Order starting from 1
        $order = 1;
        foreach ($orderedStatuses as $status) {
            $status->Status_Order = $order++;
            $status->save();
        }

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
    public function moveOrder(int $id, Request $request): JsonResponse
    {
        $direction = $request->input('direction');

        if (!in_array($direction, ['up', 'down'])) {
            return response()->json(['message' => 'Invalid direction provided.'], 422);
        }

        // Fetch the current status
        /** @var \App\Models\Status $status */
        $status = \App\Models\Status::find($id);

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

        return response()->json(['message' => 'Status order updated successfully']);
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
    public function assignDefault(int $id): JsonResponse
    {
        /** @var \App\Models\Status $status */
        $status = \App\Models\Status::find($id);

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

        return response()->json(['message' => 'Default status assigned successfully.']);
    }
}
