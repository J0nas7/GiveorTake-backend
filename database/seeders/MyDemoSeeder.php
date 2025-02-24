<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
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
                $demoTasks = [
                    // TODO
                    ['Task_Title' => "Fix broken login page UI", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-01", 'Assigned_User_ID' => 3],
                    ['Task_Title' => "Implement user profile page", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-02", 'Assigned_User_ID' => 1],
                    ['Task_Title' => "Set up database schema for product inventory", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-03", 'Assigned_User_ID' => 5],
                    ['Task_Title' => "Create API endpoints for user registration", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-04", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Write unit tests for the order service", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-05", 'Assigned_User_ID' => 4],
                    ['Task_Title' => "Design homepage layout", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-06", 'Assigned_User_ID' => 1],
                    ['Task_Title' => "Update the README file with latest setup instructions", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-07", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Integrate third-party payment gateway", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-08", 'Assigned_User_ID' => 5],
                    ['Task_Title' => "Fix CSS issues in mobile view", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-09", 'Assigned_User_ID' => 3],
                    ['Task_Title' => "Audit API performance for slow endpoints", 'Task_Status' => "To Do", 'Task_CreatedAt' => "2024-02-10", 'Assigned_User_ID' => 4],

                    // IN-PROGRESS
                    ['Task_Title' => "Refactor authentication service", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-30", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Add user role management", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-28", 'Assigned_User_ID' => 1],
                    ['Task_Title' => "Optimize product search functionality", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-27", 'Assigned_User_ID' => 5],
                    ['Task_Title' => "Integrate email notification service", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-26", 'Assigned_User_ID' => 3],
                    ['Task_Title' => "Implement infinite scroll for product list", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-25", 'Assigned_User_ID' => 4],
                    ['Task_Title' => "Add pagination to user management page", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-24", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Refactor user profile API to support file uploads", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-23", 'Assigned_User_ID' => 1],
                    ['Task_Title' => "Update product page to show dynamic pricing", 'Task_Status' => "In Progress", 'Task_CreatedAt' => "2024-01-22", 'Assigned_User_ID' => 5],

                    // REVIEW
                    ['Task_Title' => "Code review for new authentication service", 'Task_Status' => "Review", 'Task_CreatedAt' => "2024-01-15", 'Assigned_User_ID' => 3],
                    ['Task_Title' => "Test new product filtering feature", 'Task_Status' => "Review", 'Task_CreatedAt' => "2024-01-14", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Validate user role management security", 'Task_Status' => "Review", 'Task_CreatedAt' => "2024-01-13", 'Assigned_User_ID' => 4],

                    // DONE
                    ['Task_Title' => "Fix security vulnerabilities in the API", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-10", 'Assigned_User_ID' => 5],
                    ['Task_Title' => "Completed basic design for dashboard layout", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-09", 'Assigned_User_ID' => 1],
                    ['Task_Title' => "Setup CI/CD pipeline for automatic deployment", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-08", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Write API documentation for public endpoints", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-07", 'Assigned_User_ID' => 3],
                    ['Task_Title' => "Launch beta version of user onboarding flow", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-06", 'Assigned_User_ID' => 4],
                    ['Task_Title' => "Implement password reset functionality", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-05", 'Assigned_User_ID' => 2],
                    ['Task_Title' => "Integrate social login for users (Google, Facebook)", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-04", 'Assigned_User_ID' => 5],
                    ['Task_Title' => "Optimize product image upload for faster speed", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-03", 'Assigned_User_ID' => 1],
                    ['Task_Title' => "Fix bug where user is redirected after submitting the form", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-02", 'Assigned_User_ID' => 4],
                    ['Task_Title' => "Upgrade dependencies to latest versions", 'Task_Status' => "Done", 'Task_CreatedAt' => "2024-01-01", 'Assigned_User_ID' => 3],
                    ['Task_Title' => "Fix email template rendering issue", 'Task_Status' => "Done", 'Task_CreatedAt' => "2023-12-31", 'Assigned_User_ID' => 5],
                    ['Task_Title' => "Refactor legacy code for better maintainability", 'Task_Status' => "Done", 'Task_CreatedAt' => "2023-12-30", 'Assigned_User_ID' => 2],
                ];

                foreach ($demoTasks as $taskData) {
                    Task::create([
                        'Project_ID'             => $project->Project_ID,  // Associate the task with the specific project
                        'Task_Title'             => $taskData['Task_Title'],
                        'Task_Status'            => $taskData['Task_Status'],
                        'Task_CreatedAt'         => $taskData['Task_CreatedAt'],
                        'Assigned_User_ID'       => $taskData['Assigned_User_ID'],
                    ]);
                }
            }
        }
    }
}
