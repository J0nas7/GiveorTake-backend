<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\MigrationHelper;

class CreateProjectManagementTables extends Migration
{
    public function up()
    {
        // Organisations table
        Schema::create('GT_Organisations', function (Blueprint $table) {
            $prefix = 'Organisation_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('User_ID')->unsigned(); // Foreign key to Users table (creator)
            $table->string($prefix . 'Name', 255); // Organisation name
            $table->string($prefix . 'Description', 500)->nullable(); // Organisation description

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
        });

        // Teams table (linked to an organisation)
        Schema::create('GT_Teams', function (Blueprint $table) {
            $prefix = 'Team_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Organisation_ID')->unsigned(); // Foreign key to Organisations
            $table->string($prefix . 'Name', 255); // Team name
            $table->string($prefix . 'Description', 500)->nullable(); // Team description

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Organisation_ID')->references('Organisation_ID')->on('GT_Organisations')->onDelete('cascade');
        });

        Schema::create('GT_Roles', function (Blueprint $table) {
            $prefix = "Role_";

            $table->bigIncrements($prefix . 'ID');
            $table->bigInteger('Team_ID')->unsigned();
            $table->string($prefix . 'Name', 100)->unique(); // e.g. Admin, Member
            $table->string($prefix . 'Description', 500)->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields (CreatedAt, UpdatedAt, etc.)

            // Foreign Key Constraint
            $table->foreign('Team_ID')->references('Team_ID')->on('GT_Teams')->onDelete('cascade');

            // Prevent duplicate role names within the same team
            $table->unique(['Team_ID', $prefix . 'Name']);
        });

        // Team Members table (linking users to teams)
        Schema::create('GT_Team_User_Seats', function (Blueprint $table) {
            $prefix = 'Seat_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Team_ID')->unsigned(); // Foreign key to GT_Teams
            $table->bigInteger('User_ID')->unsigned(); // Foreign key to Users
            $table->bigInteger('Role_ID')->unsigned(); // Foreign key to Roles
            // $table->string($prefix . 'Role'); // Seat role (e.g., Admin, Member, etc.)
            $table->string($prefix . 'Status')->default('Active'); // Seat status (Active, Inactive, Pending, etc.)

            // Optional fields
            $table->string($prefix . 'Role_Description', 500)->nullable(); // Optional description of the role
            // $table->json($prefix . 'Permissions')->nullable(); // JSON field for storing permissions for this seat
            $table->timestamp($prefix . 'Expiration')->nullable(); // Expiration date for seat assignment (optional)

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields (CreatedAt, UpdatedAt, etc.)

            // Foreign Key Constraints
            $table->foreign('Team_ID')->references('Team_ID')->on('GT_Teams')->onDelete('cascade');
            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
            $table->foreign('Role_ID')->references('Role_ID')->on('GT_Roles')->onDelete('cascade');
        });

        Schema::create('GT_Permissions', function (Blueprint $table) {
            $prefix = "Permission_";

            $table->bigIncrements($prefix . 'ID');
            $table->bigInteger('Team_ID')->unsigned();
            $table->string($prefix . 'Key', 100)->unique(); // e.g. manageTeam.view
            $table->string($prefix . 'Description', 500)->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix);

            // Foreign Key Constraint
            $table->foreign('Team_ID')->references('Team_ID')->on('GT_Teams')->onDelete('cascade');

            // Prevent duplicate permission keys within the same team
            $table->unique(['Team_ID', $prefix . 'Key']); // Unique per team
        });

        Schema::create('GT_Role_Permissions', function (Blueprint $table) {
            $prefix = "Role_Permission_";

            $table->bigIncrements($prefix . 'ID');
            $table->bigInteger('Role_ID')->unsigned();
            $table->bigInteger('Permission_ID')->unsigned();

            $table->foreign('Role_ID')->references('Role_ID')->on('GT_Roles')->onDelete('cascade');
            $table->foreign('Permission_ID')->references('Permission_ID')->on('GT_Permissions')->onDelete('cascade');

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->unique(['Role_ID', 'Permission_ID']); // Prevent duplicates
        });

        // Projects table (linked to an organisation)
        Schema::create('GT_Projects', function (Blueprint $table) {
            $prefix = 'Project_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Team_ID')->unsigned(); // Foreign key to Organisations
            $table->string($prefix . 'Name', 255); // Project name
            $table->string($prefix . 'Key', 5); // Project key
            $table->text($prefix . 'Description')->nullable(); // Project description
            $table->enum($prefix . 'Status', ['Planned', 'Active', 'Completed', 'On Hold'])->default('Planned'); // Status
            $table->date($prefix . 'Start_Date')->nullable(); // Start date
            $table->date($prefix . 'End_Date')->nullable(); // End date

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Team_ID')->references('Team_ID')->on('GT_Teams')->onDelete('cascade');
        });

        // Backlogs table (linked to projects)
        Schema::create('GT_Backlogs', function (Blueprint $table) {
            $prefix = 'Backlog_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Project_ID')->unsigned()->nullable(); // Optional link to project
            $table->bigInteger('Team_ID')->unsigned()->nullable(); // Optional link to team
            $table->string($prefix . 'Name', 255);
            $table->text($prefix . 'Description')->nullable();
            $table->boolean($prefix . 'IsPrimary')->default(false);
            $table->dateTime($prefix . 'StartDate')->nullable();
            $table->dateTime($prefix . 'EndDate')->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('Project_ID')->references('Project_ID')->on('GT_Projects')->onDelete('set null');
            $table->foreign('Team_ID')->references('Team_ID')->on('GT_Teams')->onDelete('set null');
        });

        // Backlog Statuses table (linked to backlogs)
        Schema::create('GT_Backlog_Statuses', function (Blueprint $table) {
            $prefix = 'Status_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Backlog_ID')->unsigned(); // Foreign key to GT_Backlogs
            $table->string($prefix . 'Name', 100); // Status display name
            $table->integer($prefix . 'Order')->default(0); // Order in which status appears
            $table->boolean($prefix . 'Is_Default')->default(false); // Default status flag
            $table->boolean($prefix . 'Is_Closed')->default(false); // Closed status flag
            $table->string($prefix . 'Color', 7)->nullable(); // Optional hex color for UI

            MigrationHelper::addDateTimeFields($table, $prefix);

            $table->foreign('Backlog_ID')->references('Backlog_ID')->on('GT_Backlogs')->onDelete('cascade');
        });

        // Tasks table (assigned to backlogs & teams)
        Schema::create('GT_Tasks', function (Blueprint $table) {
            $prefix = 'Task_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger($prefix . 'Key')->unsigned(); // Project-related key to count number of tasks
            $table->bigInteger('Backlog_ID')->unsigned(); // Foreign key to Backlogs
            $table->bigInteger('Team_ID')->unsigned()->nullable(); // Optional: Assign to a team
            $table->bigInteger('Assigned_User_ID')->unsigned()->nullable(); // Optional: Assign to a user
            $table->string($prefix . 'Title', 255);
            $table->text($prefix . 'Description')->nullable();
            $table->bigInteger('Status_ID')->unsigned()->nullable(); // Add FK to new statuses
            $table->date($prefix . 'Due_Date')->nullable();
            $table->bigInteger($prefix . 'Hours_Spent')->nullable();

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Status_ID')->references('Status_ID')->on('GT_Backlog_Statuses')->onDelete('set null');
            $table->foreign('Backlog_ID')->references('Backlog_ID')->on('GT_Backlogs')->onDelete('cascade');
            $table->foreign('Team_ID')->references('Team_ID')->on('GT_Teams')->onDelete('set null');
            $table->foreign('Assigned_User_ID')->references('User_ID')->on('GT_Users')->onDelete('set null');
        });

        // Task Comments Table
        Schema::create('GT_Task_Comments', function (Blueprint $table) {
            $prefix = 'Comment_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Task_ID')->unsigned(); // Foreign key to tasks
            $table->bigInteger('Parent_Comment_ID')->unsigned()->nullable(); // Foreign key to comments (for threaded comments)
            $table->bigInteger('User_ID')->unsigned(); // Foreign key to users
            // $table->bigInteger('Time_Tracking_ID')->unsigned()->nullable(); // Foreign key to task time tracking
            $table->text($prefix . 'Text'); // Comment text

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Parent_Comment_ID')->references('Comment_ID')->on('GT_Task_Comments')->onDelete('set null');
            $table->foreign('Task_ID')->references('Task_ID')->on('GT_Tasks')->onDelete('cascade');
            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
            // $table->foreign('Time_Tracking_ID')->references('Time_Tracking_ID')->on('GT_Task_Time_Trackings')->onDelete('set null');
        });

        // Task Time Trackings Table
        Schema::create('GT_Task_Time_Trackings', function (Blueprint $table) {
            $prefix = 'Time_Tracking_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Task_ID')->unsigned(); // Foreign key to tasks
            $table->bigInteger('User_ID')->unsigned(); // Foreign key to users
            $table->bigInteger('Backlog_ID')->unsigned(); // Foreign key to projects
            // $table->bigInteger('Comment_ID')->unsigned()->nullable(); // Foreign key to task comments
            $table->dateTime($prefix . 'Start_Time'); // Start time of the tracking session
            $table->dateTime($prefix . 'End_Time')->nullable(); // End time of the tracking session (nullable if in progress)
            $table->integer($prefix . 'Duration')->unsigned()->nullable(); // Duration in minutes (optional, can be computed)
            $table->text($prefix . 'Notes')->nullable(); // Optional notes on the time entry

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Task_ID')->references('Task_ID')->on('GT_Tasks')->onDelete('cascade');
            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
            $table->foreign('Backlog_ID')->references('Backlog_ID')->on('GT_Backlogs')->onDelete('cascade');
            // $table->foreign('Comment_ID')->references('Comment_ID')->on('GT_Task_Comments')->onDelete('set null');
        });

        // Task Media Files Table
        Schema::create('GT_Task_Media_Files', function (Blueprint $table) {
            $prefix = 'Media_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('Task_ID')->unsigned(); // Foreign key to tasks
            $table->bigInteger('Uploaded_By_User_ID')->unsigned(); // Foreign key to users
            $table->string($prefix . 'File_Name', 255); // File name
            $table->string($prefix . 'File_Path', 512); // Storage path
            $table->string($prefix . 'File_Type', 100); // File type (image, video, etc.)

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('Task_ID')->references('Task_ID')->on('GT_Tasks')->onDelete('cascade');
            $table->foreign('Uploaded_By_User_ID')->references('User_ID')->on('GT_Users')->onDelete('set null');
        });

        // Activity Logs table (tracks user actions)
        Schema::create('GT_Activity_Logs', function (Blueprint $table) {
            $prefix = 'Log_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('User_ID')->unsigned(); // User who performed action
            $table->bigInteger('Project_ID')->unsigned()->nullable(); // Related project
            $table->string($prefix . 'Action', 255); // Action description
            $table->json($prefix . 'Details')->nullable(); // Store additional info

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
            $table->foreign('Project_ID')->references('Project_ID')->on('GT_Projects')->onDelete('set null');
        });

        // Notifications table (user-specific notifications)
        Schema::create('GT_Notifications', function (Blueprint $table) {
            $prefix = 'Notification_';

            $table->bigIncrements($prefix . 'ID'); // Primary key
            $table->bigInteger('User_ID')->unsigned(); // User receiving the notification
            $table->string($prefix . 'Message', 500); // Notification text
            $table->boolean($prefix . 'Read')->default(false); // Read status

            MigrationHelper::addDateTimeFields($table, $prefix); // Add common dateTime fields

            $table->foreign('User_ID')->references('User_ID')->on('GT_Users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('GT_Organisations');
        Schema::dropIfExists('GT_Teams');
        Schema::dropIfExists('GT_Roles');
        Schema::dropIfExists('GT_Team_User_Seats');
        Schema::dropIfExists('GT_Permissions');
        Schema::dropIfExists('GT_Role_Permissions');
        Schema::dropIfExists('GT_Projects');
        Schema::dropIfExists('GT_Backlogs');
        Schema::dropIfExists('GT_Backlog_Statuses');
        Schema::dropIfExists('GT_Tasks');
        Schema::dropIfExists('GT_Task_Comments');
        Schema::dropIfExists('GT_Task_Time_Trackings');
        Schema::dropIfExists('GT_Task_Media_Files');
        Schema::dropIfExists('GT_Activity_Logs');
        Schema::dropIfExists('GT_Notifications');
    }
}
