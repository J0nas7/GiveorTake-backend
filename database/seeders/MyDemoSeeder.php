<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use App\Models\Project;
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
                    'Project_Description'     => 'This project is for managing time and tasks.', // Set project description
                    'Project_Status'          => 'Active', // Project status (e.g., active)
                    'Project_Start_Date'      => now(), // Set project start date
                    'Project_End_Date'        => now()->addMonths(3), // Set project end date (3 months from now)

                    'Project_CreatedAt'       => now(),
                    'Project_UpdatedAt'       => now(),
                    'Project_DeletedAt'       => null, // Optional
                ]);

                if ($project->Project_ID) {
                    $demoTasks = [];
                    $taskNumber = 1;

                    $tasks = [
                        // TODO
                        ["Fix broken login page UI", "To Do", "2024-02-01", 3],
                        ["Implement user profile page", "To Do", "2024-02-02", 1],
                        ["Set up database schema for product inventory", "To Do", "2024-02-03", 5],
                        ["Create API endpoints for user registration", "To Do", "2024-02-04", 2],
                        ["Write unit tests for the order service", "To Do", "2024-02-05", 4],
                        ["Design homepage layout", "To Do", "2024-02-06", 1],
                        ["Update the README file with latest setup instructions", "To Do", "2024-02-07", 2],
                        ["Integrate third-party payment gateway", "To Do", "2024-02-08", 5],
                        ["Fix CSS issues in mobile view", "To Do", "2024-02-09", 3],
                        ["Audit API performance for slow endpoints", "To Do", "2024-02-10", 4],

                        // IN-PROGRESS
                        ["Refactor authentication service", "In Progress", "2024-01-30", 2],
                        ["Add user role management", "In Progress", "2024-01-28", 1],
                        ["Optimize product search functionality", "In Progress", "2024-01-27", 5],
                        ["Integrate email notification service", "In Progress", "2024-01-26", 3],
                        ["Implement infinite scroll for product list", "In Progress", "2024-01-25", 4],
                        ["Add pagination to user management page", "In Progress", "2024-01-24", 2],
                        ["Refactor user profile API to support file uploads", "In Progress", "2024-01-23", 1],
                        ["Update product page to show dynamic pricing", "In Progress", "2024-01-22", 5],

                        // Waiting for Review
                        ["Code review for new authentication service", "Waiting for Review", "2024-01-15", 3],
                        ["Test new product filtering feature", "Waiting for Review", "2024-01-14", 2],
                        ["Validate user role management security", "Waiting for Review", "2024-01-13", 4],

                        // DONE
                        ["Fix security vulnerabilities in the API", "Done", "2024-01-10", 5],
                        ["Completed basic design for dashboard layout", "Done", "2024-01-09", 1],
                        ["Setup CI/CD pipeline for automatic deployment", "Done", "2024-01-08", 2],
                        ["Write API documentation for public endpoints", "Done", "2024-01-07", 3],
                        ["Launch beta version of user onboarding flow", "Done", "2024-01-06", 4],
                        ["Implement password reset functionality", "Done", "2024-01-05", 2],
                        ["Integrate social login for users (Google, Facebook)", "Done", "2024-01-04", 5],
                        ["Optimize product image upload for faster speed", "Done", "2024-01-03", 1],
                        ["Fix bug where user is redirected after submitting the form", "Done", "2024-01-02", 4],
                        ["Upgrade dependencies to latest versions", "Done", "2024-01-01", 3],
                        ["Fix email template rendering issue", "Done", "2023-12-31", 5],
                        ["Refactor legacy code for better maintainability", "Done", "2023-12-30", 2],
                    ];

                    foreach ($tasks as $task) {
                        $demoTasks[] = [
                            'Task_Number'    => $taskNumber++,
                            'Task_Title'     => $task[0],
                            'Task_Status'    => $task[1],
                            'Task_CreatedAt' => $task[2],
                            'Assigned_User_ID' => $task[3],
                        ];
                    }

                    foreach ($demoTasks as $taskData) {
                        Task::create([
                            'Project_ID'             => $project->Project_ID,  // Associate the task with the specific project
                            'Task_Number'            => $taskData['Task_Number'],
                            'Task_Title'             => $taskData['Task_Title'],
                            'Task_Status'            => $taskData['Task_Status'],
                            'Task_CreatedAt'         => $taskData['Task_CreatedAt'],
                            'Assigned_User_ID'       => $taskData['Assigned_User_ID'],
                        ]);
                    }

                    // Fetch first 5 tasks
                    $tasks = Task::whereIn('Task_Number', [1, 2, 3, 4, 5])->get();
                    $users = User::all();

                    foreach ($tasks as $task) {
                        for ($i = 1; $i <= 5; $i++) {
                            TaskComment::create([
                                'Task_ID' => $task->Task_ID,
                                'User_ID' => $users->random()->User_ID,
                                'Comment_Text' => "Demo comment #$i for Task #{$task->Task_Number}",
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
                                'Project_ID' => $project->Project_ID,
                                'Task_ID' => $task->Task_ID,
                                'User_ID' => $users->random()->User_ID,
                                'Time_Tracking_Start_Time' => $startTime->toDateTimeString(),
                                'Time_Tracking_End_Time' => $endTime->toDateTimeString(),
                                'Time_Tracking_Duration' => $duration,
                                'Time_Tracking_Notes' => "Demo time tracking entry for Task " . $task->Task_Number . " - Entry " . ($i + 1),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
