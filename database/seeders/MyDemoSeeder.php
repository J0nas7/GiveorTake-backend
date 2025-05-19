<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use App\Models\Project;
use App\Models\Backlog;
use App\Models\Status;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamUserSeat;
use App\Models\TaskComment;
use App\Models\TaskTimeTrack;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MyDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create my own user
        $user = User::create([
            'User_FirstName'        => 'Buzz', // Change it to your firstname
            'User_Surname'          => 'Lightyear', // Change it to your surname
            'User_Email'            => 'buzz@givetake.net', // Set your email
            'User_Password'         => Hash::make('Lightyear'), // Set your password and hash it

            'User_Status'           => 1,
            'User_Remember_Token'   => Str::random(10),
            'User_ImageSrc'         => "",
            'User_CreatedAt'        => now(),
            'User_UpdatedAt'        => now(),
            'User_DeletedAt'        => null, // Optional
        ]);

        // Create other users (if needed)
        $users = [
            ['User_FirstName' => 'Alice', 'User_Surname' => 'Doe', 'User_Email' => 'alice@givetake.net'],
            ['User_FirstName' => 'Bob', 'User_Surname' => 'Smith', 'User_Email' => 'bob@givetake.net'],
            ['User_FirstName' => 'Charlie', 'User_Surname' => 'Brown', 'User_Email' => 'charlie@givetake.net'],
            ['User_FirstName' => 'David', 'User_Surname' => 'Jones', 'User_Email' => 'david@givetake.net'],
        ];

        foreach ($users as $userData) {
            User::create([
                'User_FirstName' => $userData['User_FirstName'],
                'User_Surname' => $userData['User_Surname'],
                'User_Email' => $userData['User_Email'],
                'User_Password' => Hash::make('password123'),  // You can set a default password

                'User_Status' => 1,
                'User_Remember_Token' => Str::random(10),
                'User_ImageSrc' => "",
                'User_CreatedAt' => now(),
                'User_UpdatedAt' => now(),
                'User_DeletedAt' => null,
            ]);
        }

        if ($user->User_ID) {
            // Create my organisation
            $organisation = Organisation::create([
                'User_ID'                   => $user->User_ID, // Link to the user
                'Organisation_Name'         => 'Give or Take', // Set the organisation name
                'Organisation_Description'  => 'Project Management & Time Tracking.', // Set description

                'Organisation_CreatedAt'    => now(),
                'Organisation_UpdatedAt'    => now(),
                'Organisation_DeletedAt'    => null, // Optional
            ]);

            // Create a team under the organisation
            $team = Team::create([
                'Organisation_ID'         => $organisation->Organisation_ID, // Link to the organisation
                'Team_Name'               => 'Development Team', // Set the team name
                'Team_Description'        => 'Handles all the development projects.', // Set team description
                'Team_CreatedAt'          => now(),
                'Team_UpdatedAt'          => now(),
                'Team_DeletedAt'          => null, // Optional
            ]);

            if ($team->Team_ID) {
                $seat = TeamUserSeat::create([
                    'Team_ID'   => $team->Team_ID,
                    'User_ID'   => 2,
                    'Seat_Role' => 'Member',  // Default role (change if needed)
                ]);

                // Create a project under the team
                $project = Project::create([
                    'Team_ID'                 => $team->Team_ID, // Link to the team
                    'Project_Name'            => 'Sample Project', // Set the project name
                    'Project_Key'             => 'GOT', // Set the project key
                    'Project_Description'     => 'This project is for managing time and tasks.', // Set project description
                    'Project_Status'          => 'Active', // Project status (e.g., active)
                    'Project_Start_Date'      => now(), // Set project start date
                    'Project_End_Date'        => now()->addMonths(3), // Set project end date (3 months from now)

                    'Project_CreatedAt'       => now(),
                    'Project_UpdatedAt'       => now(),
                    'Project_DeletedAt'       => null, // Optional
                ]);

                if ($project->Project_ID) {
                    // Create different types of backlogs
                    $productBacklog = Backlog::create([
                        'Project_ID' => $project->Project_ID,
                        'Team_ID' => $team->Team_ID,
                        'Backlog_Name' => 'Product Backlog',
                        'Backlog_Description' => 'Contains all features and requirements for the product.',
                        'Backlog_IsPrimary' => 1,
                        'Backlog_StartDate' => now(),
                        'Backlog_EndDate' => null,
                    ]);

                    $sprint1Backlog = Backlog::create([
                        'Project_ID' => $project->Project_ID,
                        'Team_ID' => $team->Team_ID,
                        'Backlog_Name' => 'Sprint 1 Backlog',
                        'Backlog_Description' => 'Tasks to complete in the first sprint.',
                        'Backlog_IsPrimary' => 0,
                        'Backlog_StartDate' => now()->subDays(14),
                        'Backlog_EndDate' => now()->subDays(7),
                    ]);

                    $sprint2Backlog = Backlog::create([
                        'Project_ID' => $project->Project_ID,
                        'Team_ID' => $team->Team_ID,
                        'Backlog_Name' => 'Sprint 2 Backlog',
                        'Backlog_Description' => 'Tasks to complete in the second sprint.',
                        'Backlog_IsPrimary' => 0,
                        'Backlog_StartDate' => now()->subDays(7),
                        'Backlog_EndDate' => now(),
                    ]);

                    $bugBacklog = Backlog::create([
                        'Project_ID' => $project->Project_ID,
                        'Team_ID' => $team->Team_ID,
                        'Backlog_Name' => 'Bug Backlog',
                        'Backlog_Description' => 'List of known issues to fix.',
                        'Backlog_IsPrimary' => 0,
                        'Backlog_StartDate' => now()->subMonth(),
                        'Backlog_EndDate' => null,
                    ]);

                    $techBacklog = Backlog::create([
                        'Project_ID' => $project->Project_ID,
                        'Team_ID' => $team->Team_ID,
                        'Backlog_Name' => 'Technical Backlog',
                        'Backlog_Description' => 'Includes refactoring and architectural tasks.',
                        'Backlog_IsPrimary' => 0,
                        'Backlog_StartDate' => now()->subWeeks(3),
                        'Backlog_EndDate' => null,
                    ]);

                    $taskNumber = 1;

                    function seedStatusesForBacklog($backlog): array
                    {
                        $preset = match ($backlog->Backlog_Name) {
                            'Product Backlog' => [
                                'Idea'      => ['color' => '#6c757d', 'order' => 1, 'is_default' => true],
                                'Planned'   => ['color' => '#3490dc', 'order' => 2],
                                'Approved'  => ['color' => '#38c172', 'order' => 3],
                                'Discarded' => ['color' => '#e3342f', 'order' => 4, 'is_closed' => true],
                            ],
                            'Sprint 1 Backlog', 'Sprint 2 Backlog' => [
                                'To Do'        => ['color' => '#3490dc', 'order' => 1, 'is_default' => true],
                                'In Progress'  => ['color' => '#f6993f', 'order' => 2],
                                'Code Review'  => ['color' => '#9561e2', 'order' => 3],
                                'Done'         => ['color' => '#38c172', 'order' => 4, 'is_closed' => true],
                            ],
                            'Bug Backlog' => [
                                'Reported'       => ['color' => '#6cb2eb', 'order' => 1, 'is_default' => true],
                                'Acknowledged'   => ['color' => '#ffed4a', 'order' => 2],
                                'Fixing/Testing' => ['color' => '#f6993f', 'order' => 3],
                                'Resolved'       => ['color' => '#9561e2', 'order' => 4, 'is_closed' => true],
                            ],
                            'Technical Backlog' => [
                                'Planned'     => ['color' => '#3490dc', 'order' => 1, 'is_default' => true],
                                'Blocked'     => ['color' => '#e3342f', 'order' => 2],
                                'In Progress' => ['color' => '#f6993f', 'order' => 3],
                                'Completed'   => ['color' => '#38c172', 'order' => 4, 'is_closed' => true],
                            ],
                            default => [],
                        };

                        $statusMap = [];

                        $i = 1;
                        foreach ($preset as $name => $props) {
                            $status = Status::create([
                                'Backlog_ID'        => $backlog->Backlog_ID,
                                'Status_Name'       => $name,
                                'Status_Key'        => strtolower(str_replace(' ', '_', $name)),
                                'Status_Order'      => $props['order'],
                                'Status_Is_Default' => $props['is_default'] ?? false,
                                'Status_Is_Closed'  => $props['is_closed'] ?? false,
                                'Status_Color'      => $props['color'],
                            ]);

                            $statusMap[$i] = $status->Status_ID;
                            $i++;
                        }

                        return $statusMap;
                    }

                    function createTasksForBacklog($tasks, $backlog, $teamId, &$taskNumber)
                    {
                        $statusMap = seedStatusesForBacklog($backlog);

                        foreach ($tasks as $task) {
                            Task::create([
                                'Task_Key'         => $taskNumber++,
                                'Backlog_ID'       => $backlog->Backlog_ID,
                                'Team_ID'          => $teamId,
                                'Assigned_User_ID' => $task[3] ?? null,
                                'Task_Title'       => $task[0],
                                'Status_ID'        => $statusMap[$task[1]] ?? null, // Lookup by status name
                                'Task_CreatedAt'   => $task[2],
                            ]);
                        }
                    }

                    // Define sample tasks for each backlog
                    createTasksForBacklog([
                        ["Design homepage layout", rand(1, 4), "2024-02-01", 1],
                        ["Set up database schema", rand(1, 4), "2024-02-02", 2],
                        ["Create registration API", rand(1, 4), "2024-02-03", 3],
                        ["Implement user profile", rand(1, 4), "2024-02-04", 4],
                    ], $productBacklog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ["Fix CSS issues in mobile view", rand(1, 4), "2024-01-27", 1],
                        ["Add pagination to user list", rand(1, 4), "2024-01-26", 2],
                        ["Refactor authentication service", rand(1, 4), "2024-01-25", 3],
                    ], $sprint1Backlog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ["Integrate payment gateway", rand(1, 4), "2024-01-20", 4],
                        ["Write unit tests for order service", rand(1, 4), "2024-01-19", 2],
                        ["Optimize search functionality", rand(1, 4), "2024-01-18", 5],
                    ], $sprint2Backlog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ["Fix redirect bug on form submit", rand(1, 4), "2024-01-05", 3],
                        ["Resolve API performance issue", rand(1, 4), "2024-01-06", 1],
                    ], $bugBacklog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ["Refactor legacy codebase", rand(1, 4), "2024-01-04", 2],
                        ["Implement service-layer pattern", rand(1, 4), "2024-01-03", 5],
                    ], $techBacklog, $team->Team_ID, $taskNumber);

                    // Fetch first 5 tasks
                    $tasks = Task::whereIn('Task_ID', [1, 2, 3, 4, 5])->get();
                    $users = User::all();

                    foreach ($tasks as $task) {
                        for ($i = 1; $i <= 5; $i++) {
                            TaskComment::create([
                                'Task_ID' => $task->Task_ID,
                                'User_ID' => $users->random()->User_ID,
                                'Comment_Text' => "Demo comment #$i for Task #{$task->Task_Key}",
                                'Comment_CreatedAt' => now(),
                                'Comment_UpdatedAt' => now(),
                            ]);
                        }
                    }

                    $tasks = Task::all();

                    // Iterate through tasks to generate time tracking data
                    foreach ($tasks as $task) {
                        // Create 6 random time tracking entries for each task
                        for ($i = 0; $i < 6; $i++) {
                            $startTime = Carbon::now()->subDays(rand(0, 60)); // Random start time within this year
                            $endTime = $startTime->copy()->addMinutes(rand(30, 240)); // Random end time (30 to 240 minutes after start)

                            $duration = $startTime->diffInSeconds($endTime); // Calculate duration in seconds

                            // Insert demo time track
                            TaskTimeTrack::create([
                                'Backlog_ID' => $task->Backlog_ID,
                                'Task_ID' => $task->Task_ID,
                                'User_ID' => $users->random()->User_ID,
                                'Time_Tracking_Start_Time' => $startTime->toDateTimeString(),
                                'Time_Tracking_End_Time' => $endTime->toDateTimeString(),
                                'Time_Tracking_Duration' => $duration,
                                'Time_Tracking_Notes' => "Demo time tracking entry for Task " . $task->Task_Key . " - Entry " . ($i + 1),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
