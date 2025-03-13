<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class UtilityController extends Controller
{
    /**
     * Search in every column across all tables in the database,
     * but only return records affiliated with the given User_ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function globalSearch(int $userId, string $searchString): JsonResponse
    {
        if (!$searchString) {
            return response()->json(['error' => 'Search string is required.'], 400);
        }

        if (!$userId) {
            return response()->json(['error' => 'User ID is required.'], 400);
        }

        $results = [];

        // Check if $searchString matches the pattern (3-5 letters, hyphen, number)
        if (preg_match('/^([A-Za-z]{3,5})-(\d+)$/', $searchString, $matches)) {
            $key = $matches[1]; // Extract project key
            $number = $matches[2]; // Extract task number

            // Find the project with the matching key
            $project = Project::where('Project_Key', $key)
                ->whereHas('team', function ($query) use ($userId) {
                    $query->whereHas('organisation', function ($subQuery) use ($userId) {
                        $subQuery->where('User_ID', $userId); // User is the owner of the organisation
                    })->orWhereHas('userSeats', function ($subQuery) use ($userId) {
                        $subQuery->where('User_ID', $userId); // User has a seat in the team
                    });
                })
                ->first();

            if ($project) {
                // Find matching tasks within the project
                $tasks = Task::with('project')
                    ->where('Task_Key', $number)
                    ->where('Project_ID', $project->Project_ID) // Ensure task belongs to the project
                    ->get();

                if (!$tasks->isEmpty()) {
                    $results['GT_Tasks'] = $tasks;
                }
            }
        }

        // Get all table names in SQLite
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");

        foreach ($tables as $table) {
            $tableName = $table->name;

            // Get columns of the current table
            $columns = DB::select("PRAGMA table_info($tableName)");

            // Check if User_ID exists in the columns
            $hasUserIdColumn = collect($columns)->contains(function ($column) {
                return $column->name === 'User_ID';
            });

            // Check if the table is 'GT_Tasks', so we can include 'with' for eager loading
            if ($tableName === 'GT_Tasks') {
                // Use 'with' to eager load the 'project' relationship when searching in the 'GT_Tasks' table
                $query = Task::with('project');
            } else {
                // Start building the query to search through columns
                $query = DB::table($tableName);
                $first = true;
            }


            foreach ($columns as $column) {
                // Skip primary keys or unwanted columns (Optional: Add logic to exclude certain columns)
                if ($column->name === 'User_ID') {
                    continue; // Skip User_ID for search
                }

                if ($first) {
                    $query->where($column->name, 'LIKE', "%$searchString%");
                    $first = false;
                } else {
                    $query->orWhere($column->name, 'LIKE', "%$searchString%");
                }
            }

            // If the table has a User_ID column, filter the results by the User_ID
            if ($hasUserIdColumn) {
                $query->where('User_ID', $userId);
            }

            // Execute the query
            $data = $query->get();

            // Add results if found
            if (!$data->isEmpty()) {
                $results[$tableName] = $data;
            }
        }

        // Return the search results
        return response()->json($results);
    }
}
