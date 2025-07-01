<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use App\Models\Project;
use App\Models\Backlog;
use App\Models\Permission;
use App\Models\Role;
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

class DunderMifflinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create lead journalist user
        $user = User::create([
            'User_FirstName'      => 'Michael',
            'User_Surname'        => 'Scott',
            'User_Email'          => 'michael@dundermifflin.com',
            'User_Password'       => Hash::make('WorldsBestBoss'),
            'User_Status'         => 1,
            'User_Remember_Token' => Str::random(10),
            'User_ImageSrc'       => "",
            'User_CreatedAt'      => now(),
            'User_UpdatedAt'      => now(),
            'User_DeletedAt'      => null,
        ]);

        // Other journalists
        $users = [
            ['User_FirstName' => 'Pam', 'User_Surname' => 'Beesly', 'User_Email' => 'pam@dundermifflin.com'],
            ['User_FirstName' => 'Jim', 'User_Surname' => 'Halpert', 'User_Email' => 'jim@dundermifflin.com'],
            ['User_FirstName' => 'Dwight', 'User_Surname' => 'Schrute', 'User_Email' => 'dwight@dundermifflin.com'],
            ['User_FirstName' => 'Angela', 'User_Surname' => 'Martin', 'User_Email' => 'angela@dundermifflin.com'],
        ];

        foreach ($users as $userData) {
            User::create([
                'User_FirstName'      => $userData['User_FirstName'],
                'User_Surname'        => $userData['User_Surname'],
                'User_Email'          => $userData['User_Email'],
                'User_Password'       => Hash::make('password123'),
                'User_Status'         => 1,
                'User_Remember_Token' => Str::random(10),
                'User_ImageSrc'       => "",
                'User_CreatedAt'      => now(),
                'User_UpdatedAt'      => now(),
                'User_DeletedAt'      => null,
            ]);
        }

        if ($user->User_ID) {
            // Create organisation
            $organisation = Organisation::create([
                'User_ID'                  => $user->User_ID,
                'Organisation_Name'        => 'Dunder Mifflin News',
                'Organisation_Description' => 'TV & Paper News Company',
                'Organisation_CreatedAt'   => now(),
                'Organisation_UpdatedAt'   => now(),
                'Organisation_DeletedAt'   => null,
            ]);

            // Create journalist team
            $team = Team::create([
                'Organisation_ID'   => $organisation->Organisation_ID,
                'Team_Name'         => 'Journalism Team',
                'Team_Description'  => 'Handles all news coverage and editorial tasks.',
                'Team_CreatedAt'    => now(),
                'Team_UpdatedAt'    => now(),
                'Team_DeletedAt'    => null,
            ]);

            if ($team->Team_ID) {
                // Roles: Editor and Reporter
                $editorRole = Role::create([
                    'Team_ID'          => $team->Team_ID,
                    'Role_Name'        => 'Editor',
                    'Role_Description' => 'Oversees news projects and approves content.',
                    'Role_CreatedAt'   => now(),
                    'Role_UpdatedAt'   => now(),
                ]);

                $reporterRole = Role::create([
                    'Team_ID'          => $team->Team_ID,
                    'Role_Name'        => 'Reporter',
                    'Role_Description' => 'Writes and researches news articles.',
                    'Role_CreatedAt'   => now(),
                    'Role_UpdatedAt'   => now(),
                ]);

                // Assign users to team with roles (Michael as Editor, others Reporters)
                TeamUserSeat::create([
                    'Team_ID'             => $team->Team_ID,
                    'User_ID'             => $user->User_ID, // Michael
                    'Role_ID'             => $editorRole->Role_ID,
                    'Seat_Status'         => 'Active',
                    'Seat_Role_Description' => 'Team lead editor',
                    'Seat_Expiration'     => null,
                    'Seat_CreatedAt'      => now(),
                    'Seat_UpdatedAt'      => now(),
                ]);

                // Assign other users as Reporters
                $reporterUserIds = User::whereIn('User_Email', [
                    'pam@dundermifflin.com',
                    'jim@dundermifflin.com',
                    'dwight@dundermifflin.com',
                    'angela@dundermifflin.com',
                ])->pluck('User_ID');

                foreach ($reporterUserIds as $userId) {
                    TeamUserSeat::create([
                        'Team_ID'               => $team->Team_ID,
                        'User_ID'               => $userId,
                        'Role_ID'               => $reporterRole->Role_ID,
                        'Seat_Status'           => 'Active',
                        'Seat_Role_Description' => 'Reporter role',
                        'Seat_Expiration'       => null,
                        'Seat_CreatedAt'        => now(),
                        'Seat_UpdatedAt'        => now(),
                    ]);
                }

                // Create projects (news topics)
                $project = Project::create([
                    'Team_ID'              => $team->Team_ID,
                    'Project_Name'         => 'Election 2024 Coverage',
                    'Project_Key'          => 'E24',
                    'Project_Description'  => 'Coverage and reporting on the 2024 national elections.',
                    'Project_Status'       => 'Active',
                    'Project_Start_Date'   => now(),
                    'Project_End_Date'     => now()->addMonths(2),
                    'Project_CreatedAt'    => now(),
                    'Project_UpdatedAt'    => now(),
                    'Project_DeletedAt'    => null,
                ]);

                if ($project->Project_ID) {
                    // Create backlogs relevant for journalism
                    $newsIdeaBacklog = Backlog::create([
                        'Project_ID'          => $project->Project_ID,
                        'Team_ID'             => $team->Team_ID,
                        'Backlog_Name'        => 'News Ideas',
                        'Backlog_Description' => 'Potential news stories and leads.',
                        'Backlog_IsPrimary'   => 1,
                        'Backlog_StartDate'   => now(),
                        'Backlog_EndDate'     => null,
                    ]);

                    $currentNewsBacklog = Backlog::create([
                        'Project_ID'          => $project->Project_ID,
                        'Team_ID'             => $team->Team_ID,
                        'Backlog_Name'        => 'Current News',
                        'Backlog_Description' => 'Ongoing news reports and stories in progress.',
                        'Backlog_IsPrimary'   => 0,
                        'Backlog_StartDate'   => now()->subDays(7),
                        'Backlog_EndDate'     => null,
                    ]);

                    $editorialReviewBacklog = Backlog::create([
                        'Project_ID'          => $project->Project_ID,
                        'Team_ID'             => $team->Team_ID,
                        'Backlog_Name'        => 'Editorial Review',
                        'Backlog_Description' => 'Stories under editorial review.',
                        'Backlog_IsPrimary'   => 0,
                        'Backlog_StartDate'   => now()->subDays(3),
                        'Backlog_EndDate'     => null,
                    ]);

                    $factCheckBacklog = Backlog::create([
                        'Project_ID'          => $project->Project_ID,
                        'Team_ID'             => $team->Team_ID,
                        'Backlog_Name'        => 'Fact-Check & Corrections',
                        'Backlog_Description' => 'Reported errors and fact-checking tasks.',
                        'Backlog_IsPrimary'   => 0,
                        'Backlog_StartDate'   => now()->subMonth(),
                        'Backlog_EndDate'     => null,
                    ]);

                    // Generating and assigning RBAC permissions per project and backlog (admin gets "manage", member gets "access")
                    // 1. Collect all backlogs into an array
                    $backlogs = [$newsIdeaBacklog, $currentNewsBacklog, $editorialReviewBacklog, $factCheckBacklog];

                    // 2. Permissions container
                    $permissionsToAttachToEditor = [];
                    $permissionsToAttachToReporter = [];

                    $availablePermissions = ["Modify Organisation Settings", "Modify Team Settings", "Manage Team Members"];

                    foreach ($availablePermissions as $permData) {
                        $perm = Permission::create([
                            'Team_ID'                => $team->Team_ID,
                            'Permission_Key'         => $permData,
                            'Permission_Description' => 'Description',
                            'Permission_CreatedAt'   => now(),
                            'Permission_UpdatedAt'   => now(),
                        ]);

                        $permissionsToAttachToEditor[] = $perm->Permission_ID;
                    }

                    // 3. Project-level permissions
                    $projectPermissions = [
                        [
                            'key' => "accessProject.{$project->Project_ID}",
                            'description' => "Access Project: {$project->Project_Name}",
                            'assignTo' => 'both',
                        ],
                        [
                            'key' => "manageProject.{$project->Project_ID}",
                            'description' => "Manage Project: {$project->Project_Name}",
                            'assignTo' => 'admin',
                        ]
                    ];

                    foreach ($projectPermissions as $permData) {
                        $perm = Permission::create([
                            'Team_ID'                => $team->Team_ID,
                            'Permission_Key'         => $permData['key'],
                            'Permission_Description' => $permData['description'],
                            'Permission_CreatedAt'   => now(),
                            'Permission_UpdatedAt'   => now(),
                        ]);

                        if ($permData['assignTo'] === 'admin' || $permData['assignTo'] === 'both') {
                            $permissionsToAttachToEditor[] = $perm->Permission_ID;
                        }
                        if ($permData['assignTo'] === 'both') {
                            $permissionsToAttachToReporter[] = $perm->Permission_ID;
                        }
                    }

                    // 4. Backlog-level permissions
                    foreach ($backlogs as $backlog) {
                        $accessKey = "accessBacklog.{$backlog->Backlog_ID}";
                        $manageKey = "manageBacklog.{$backlog->Backlog_ID}";

                        $accessPerm = Permission::create([
                            'Team_ID' => $team->Team_ID,
                            'Permission_Key' => $accessKey,
                            'Permission_Description' => "Access Backlog: {$backlog->Backlog_Name}",
                            'Permission_CreatedAt' => now(),
                            'Permission_UpdatedAt' => now(),
                        ]);

                        $managePerm = Permission::create([
                            'Team_ID' => $team->Team_ID,
                            'Permission_Key' => $manageKey,
                            'Permission_Description' => "Manage Backlog: {$backlog->Backlog_Name}",
                            'Permission_CreatedAt' => now(),
                            'Permission_UpdatedAt' => now(),
                        ]);

                        $permissionsToAttachToEditor[] = $accessPerm->Permission_ID;
                        $permissionsToAttachToEditor[] = $managePerm->Permission_ID;
                        $permissionsToAttachToReporter[] = $accessPerm->Permission_ID;
                    }

                    // 5. Attach to roles
                    $editorRole->permissions()->attach($permissionsToAttachToEditor);
                    $reporterRole->permissions()->attach($permissionsToAttachToReporter);

                    // Task number counter
                    $taskNumber = 1;

                    // Helper function for seeding statuses per backlog
                    function seedStatusesForBacklog($backlog): array
                    {
                        $preset = match ($backlog->Backlog_Name) {
                            'News Ideas' => [
                                'Idea'      => ['color' => '#6c757d', 'order' => 1, 'is_default' => true],
                                'Approved'  => ['color' => '#38c172', 'order' => 2],
                                'Rejected'  => ['color' => '#e3342f', 'order' => 3, 'is_closed' => true],
                            ],
                            'Current News' => [
                                'Assigned'     => ['color' => '#3490dc', 'order' => 1, 'is_default' => true],
                                'In Progress'  => ['color' => '#f6993f', 'order' => 2],
                                'Under Review' => ['color' => '#9561e2', 'order' => 3],
                                'Published'    => ['color' => '#38c172', 'order' => 4, 'is_closed' => true],
                            ],
                            'Editorial Review' => [
                                'Under Review' => ['color' => '#9561e2', 'order' => 1, 'is_default' => true],
                                'Approved'     => ['color' => '#38c172', 'order' => 2],
                                'Rejected'     => ['color' => '#e3342f', 'order' => 3, 'is_closed' => true],
                            ],
                            'Fact-Check & Corrections' => [
                                'Assigned'   => ['color' => '#3490dc', 'order' => 1, 'is_default' => true],
                                'Verifying'  => ['color' => '#f6993f', 'order' => 2],
                                'Confirmed'  => ['color' => '#38c172', 'order' => 3],
                                'Flagged'    => ['color' => '#e3342f', 'order' => 4, 'is_closed' => true],
                            ],
                            default => [],
                        };

                        $statusMap = [];
                        $i = 1;
                        foreach ($preset as $name => $props) {
                            $status = Status::create([
                                'Backlog_ID'        => $backlog->Backlog_ID,
                                'Status_Name'       => $name,
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

                    // Task creation helper
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
                                'Status_ID'        => $statusMap[$task[1]] ?? null,
                                'Task_Description' => $task[4] ?? null,
                                'Task_CreatedAt'   => $task[2],
                            ]);
                        }
                    }

                    // Seed tasks for each backlog
                    createTasksForBacklog([
                        ['Interview candidate for election story', 1, now(), $reporterUserIds->first(), 'Schedule and conduct interview with mayoral candidate.'],
                        ['Write article on election polling', 2, now(), $reporterUserIds->get(1), 'Analyze recent polls and draft article.'],
                        ['Pitch feature on local business recovery', 1, now(), $reporterUserIds->get(0), 'Develop pitch around small businesses bouncing back post-pandemic.'],
                        ['Investigate lead poisoning complaints', 2, now(), $reporterUserIds->get(1), 'Check sources and outline a potential exposé.'],
                        ['Create survey for reader feedback', 1, now(), $reporterUserIds->get(0), 'Prepare a short reader engagement survey.'],
                        ['Draft proposal for education series', 2, now(), $reporterUserIds->get(2), 'Plan out topics for public school reporting series.'],
                        ['Brainstorm community spotlight ideas', 1, now(), $reporterUserIds->first(), 'Create list of potential profiles in underserved areas.'],
                    ], $newsIdeaBacklog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ['Cover city council meeting', 1, now(), $reporterUserIds->first(), 'Attend and report on the upcoming city council meeting.'],
                        ['Research background for housing article', 2, now(), $reporterUserIds->get(1), 'Gather data and expert opinions on affordable housing.'],
                        ['Write follow-up on local flood response', 2, now(), $reporterUserIds->get(2), 'Update on city’s actions since last storm.'],
                        ['Develop piece on rising food costs', 3, now(), $reporterUserIds->get(1), 'Interview local grocery owners and analyze pricing trends.'],
                        ['Create graphics for transportation project', 1, now(), $reporterUserIds->get(0), 'Coordinate with design team for charts/maps.'],
                        ['Rewrite breaking news blurb for print', 2, now(), $reporterUserIds->first(), 'Polish initial post for newspaper publication.'],
                        ['Produce social media video for news story', 3, now(), $reporterUserIds->get(2), 'Summarize article in a 60-second video.'],
                    ], $currentNewsBacklog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ['Editorial review for draft article', 1, now(), $user->User_ID, 'Editor to review draft before publishing.'],
                        ['Review op-ed submission', 2, now(), $user->User_ID, 'Check tone and facts in the guest op-ed.'],
                        ['Finalize layout for Sunday feature', 2, now(), $user->User_ID, 'Ensure story layout follows section standards.'],
                        ['Approve infographics for economic piece', 1, now(), $user->User_ID, 'Verify visual accuracy and clarity.'],
                        ['Review corrections log', 3, now(), $user->User_ID, 'Audit past corrections and ensure follow-up where needed.'],
                        ['Give feedback on investigative report', 2, now(), $user->User_ID, 'Provide structural and factual critique.'],
                        ['Check photos for sensitive content', 1, now(), $user->User_ID, 'Review submitted images for editorial standards.'],
                    ], $editorialReviewBacklog, $team->Team_ID, $taskNumber);

                    createTasksForBacklog([
                        ['Fact-check claims in recent story', 1, now(), $reporterUserIds->get(2), 'Verify all facts before publishing.'],
                        ['Validate quotes in climate report', 2, now(), $reporterUserIds->get(2), 'Cross-check interviews with audio recordings.'],
                        ['Check statistical data in opioid article', 3, now(), $reporterUserIds->get(1), 'Recalculate figures from public health sources.'],
                        ['Investigate misleading chart on page A3', 2, now(), $reporterUserIds->get(0), 'Clarify source and interpretation.'],
                        ['Audit source list in longform article', 1, now(), $reporterUserIds->get(2), 'Ensure source diversity and credibility.'],
                        ['Re-confirm quotes attributed to official', 3, now(), $reporterUserIds->get(2), 'Reach out for double confirmation.'],
                        ['Verify historical references in op-ed', 2, now(), $reporterUserIds->get(1), 'Ensure events and dates are accurate.'],
                    ], $factCheckBacklog, $team->Team_ID, $taskNumber);

                    $tasks = Task::all();

                    // Iterate through tasks to generate time tracking data
                    foreach ($tasks as $task) {
                        // Create 6 random time tracking entries for each task
                        for ($i = 0; $i < 6; $i++) {
                            $startTime = Carbon::now()->subDays(rand(0, 14)); // Random start time within the past two weeks
                            $endTime = $startTime->copy()->addMinutes(rand(30, 240)); // Random end time (30 to 240 minutes after start)

                            $duration = $startTime->diffInSeconds($endTime); // Calculate duration in seconds

                            // Insert demo time track
                            TaskTimeTrack::create([
                                'Backlog_ID' => $task->Backlog_ID,
                                'Task_ID' => $task->Task_ID,
                                'User_ID' => User::inRandomOrder()->first()->User_ID,
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
