<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;

class GTTaskSeeder extends Seeder
{
    public function run()
    {
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
            Task::factory()->fromDemoData($taskData)->create();
        }
    }
}
?>