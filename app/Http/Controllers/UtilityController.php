<?php

namespace App\Http\Controllers;

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

        // Get all table names in SQLite
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");

        $results = [];

        foreach ($tables as $table) {
            $tableName = $table->name;

            // Get columns of the current table
            $columns = DB::select("PRAGMA table_info($tableName)");

            // Check if User_ID exists in the columns
            $hasUserIdColumn = collect($columns)->contains(function ($column) {
                return $column->name === 'User_ID';
            });

            // Start building the query to search through columns
            $query = DB::table($tableName);
            $first = true;

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
