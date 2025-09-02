<?php

namespace App\Http\Controllers;

use App\Models\Backlog;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskMediaFile;
use App\Models\Team;
use App\Models\TeamUserSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UtilityController extends Controller
{
    /**
     * Search in every column across all tables in the database,
     * but only return records affiliated with the given User_ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function globalSearch(string $searchString): JsonResponse
    {
        if (!$searchString) {
            return response()->json(['error' => 'Search string is required.'], 400);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User is required.'], 400);
        }
        $userId = $user->User_ID;

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

    public function search(string $searchString): JsonResponse
    {
        if (!$searchString) {
            return response()->json(['error' => 'Search string is required.'], 400);
        }

        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['error' => 'User is required.'], 400);
        }

        // Get organisations where the user is the owner
        $ownedOrganisations = Organisation::where('User_ID', $user->User_ID)->pluck('Organisation_ID');

        // Get teams where the user is the owner of the organisation
        $ownedTeams = Team::whereIn('Organisation_ID', $ownedOrganisations)->pluck('Team_ID');

        // Get teams where the user has a seat
        $teamsWithSeat = TeamUserSeat::where('User_ID', $user->User_ID)->pluck('Team_ID');

        // Merge both sets of team IDs
        $allowedTeamIds = $ownedTeams->merge($teamsWithSeat);

        $results = [];

        // Search in Organisations (that have a team with allowedTeamIds)
        $tableName = "GT_Organisations";
        $organisationColumns = Schema::getColumnListing($tableName);
        $organisationResults = Organisation::whereIn('Organisation_ID', function ($query) use ($allowedTeamIds) {
            $query->select('Organisation_ID')
                ->from('GT_Teams')
                ->whereIn('Team_ID', $allowedTeamIds);
        })
            ->where(function ($query) use ($organisationColumns, $searchString) {
                foreach ($organisationColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();

        if (!$organisationResults->isEmpty()) {
            $results[$tableName] = $organisationResults;
        }

        // Search in Teams
        $tableName = "GT_Teams";
        $teamColumns = Schema::getColumnListing($tableName);
        $teamResults = Team::whereIn('Team_ID', $allowedTeamIds)
            ->where(function ($query) use ($teamColumns, $searchString) {
                foreach ($teamColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();

        if (!$teamResults->isEmpty()) {
            $results[$tableName] = $teamResults;
        }

        // Search in Projects
        $tableName = "GT_Projects";
        $projectColumns = Schema::getColumnListing($tableName);
        $projectResults = Project::whereIn('Team_ID', $allowedTeamIds)
            ->where(function ($query) use ($projectColumns, $searchString) {
                foreach ($projectColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();
        if (!$projectResults->isEmpty()) {
            $results[$tableName] = $projectResults;
        }

        // Search in Backlogs
        $tableName = "GT_Backlogs";
        $backlogColumns = Schema::getColumnListing($tableName);
        $backlogResults = Backlog::whereIn('Team_ID', $allowedTeamIds)
            ->where(function ($query) use ($backlogColumns, $searchString) {
                foreach ($backlogColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();
        if (!$backlogResults->isEmpty()) {
            $results[$tableName] = $backlogResults;
        }

        // Search in Tasks
        $tableName = "GT_Tasks";
        $taskColumns = Schema::getColumnListing($tableName);
        $taskResults = Task::with('backlog.project', 'status', 'user')
            ->whereIn('Team_ID', $allowedTeamIds)
            ->where(function ($query) use ($taskColumns, $searchString) {
                foreach ($taskColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();
        if (!$taskResults->isEmpty()) {
            $results[$tableName] = $taskResults;
        }

        // Search in TaskMediaFiles
        $tableName = "GT_Task_Media_Files";
        $taskMediaFileColumns = Schema::getColumnListing($tableName);
        $taskMediaFileResults = TaskMediaFile::with('task.backlog.project')
            ->whereIn('Task_ID', function ($query) use ($allowedTeamIds) {
                $query->select('Task_ID')->from('GT_Tasks')->whereIn('Team_ID', $allowedTeamIds);
            })
            ->where(function ($query) use ($taskMediaFileColumns, $searchString) {
                foreach ($taskMediaFileColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();
        if (!$taskMediaFileResults->isEmpty()) {
            $results[$tableName] = $taskMediaFileResults;
        }

        // Search in TaskComments
        $tableName = "GT_Task_Comments";
        $taskCommentColumns = Schema::getColumnListing($tableName);
        $taskCommentResults = TaskComment::with('task.backlog.project')
            ->whereIn('Task_ID', function ($query) use ($allowedTeamIds) {
                $query->select('Task_ID')->from('GT_Tasks')->whereIn('Team_ID', $allowedTeamIds);
            })
            ->where(function ($query) use ($taskCommentColumns, $searchString) {
                foreach ($taskCommentColumns as $column) {
                    $query->orWhere($column, 'like', "%{$searchString}%");
                }
            })
            ->get();

        if (!$taskCommentResults->isEmpty()) {
            $results[$tableName] = $taskCommentResults;
        }

        return response()->json($results);
    }
}
