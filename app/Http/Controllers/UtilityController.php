<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskMediaFile;
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

        $driver = DB::getDriverName();

        // Get all table names in database
        if ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
        } elseif ($driver === 'pgsql') {
            $tables = DB::select("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = 'public'");
        } else {
            return response()->json(['error' => 'Unsupported database driver.'], 500);
        }

        foreach ($tables as $table) {
            $tableName = $table->name;

            // Get columns of the current table, per driver
            if ($driver === 'sqlite') {
                $columns = DB::select("PRAGMA table_info($tableName)");
                $columnNames = collect($columns)->pluck('name');
            } elseif ($driver === 'pgsql') {
                $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = ?", [$tableName]);
                $columnNames = collect($columns)->pluck('column_name');
            }

            // Skip Laravel’s migration tables if necessary
            if (in_array($tableName, ['migrations', 'password_resets', 'failed_jobs'])) {
                continue;
            }
            // Skip tables that should not be searched
            if (in_array($tableName, [
                'GT_Users',
                'GT_Backlog_Statuses',
                'GT_Permissions',
                'GT_Roles',
                'GT_Role_Permissions',
                'GT_Team_User_Seats',
                'GT_Task_Time_Trackings',
                'GT_Activity_Logs'
            ])) {
                continue;
            }

            // Check if User_ID exists in the columns
            $hasUserIdColumn = $columnNames->contains('User_ID');
            // $hasUserIdColumn = collect($columnNames)->contains(function ($column) {
            //     return $column->name === 'User_ID';
            // });

            // Check if the table is 'GT_Tasks', so we can include 'with' for eager loading
            if ($tableName === 'GT_Tasks') {
                // Use 'with' to eager load the 'backlog.project' and the 'status' relationship when searching in the 'GT_Tasks' table
                $query = Task::with('backlog.project', 'status', 'user');
            } else if ($tableName === 'GT_Task_Media_Files') {
                $query = TaskMediaFile::with('task.backlog.project');
            } else if ($tableName === 'GT_Task_Comments') {
                $query = TaskComment::with('task.backlog.project');
            } else {
                // Start building the query to search through columns
                $query = DB::table($tableName);
                $first = true;
            }

            foreach ($columnNames as $column) {
                // Skip primary keys or unwanted columns
                if ($column === 'User_ID') {
                    continue; // Skip User_ID for search
                }

                if ($first) {
                    $query->where($column, 'LIKE', "%$searchString%");
                    $first = false;
                } else {
                    $query->orWhere($column, 'LIKE', "%$searchString%");
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
