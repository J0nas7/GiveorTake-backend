<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use App\Models\Project;

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

            // Create a project under my organisation
            $project = Project::create([
                'Organisation_ID'       => $organisation->Organisation_ID, // Link to the organisation
                'Project_Name'          => 'Sample Project', // Set the project name
                'Project_Description'   => 'This project is for managing time and tasks.', // Set project description
                'Project_Status'        => 'Active', // Project status (e.g., active)
                'Project_Start_Date'    => now(), // Set project start date
                'Project_End_Date'      => now()->addMonths(3), // Set project end date (3 months from now)
                
                'Project_CreatedAt'     => now(),
                'Project_UpdatedAt'     => now(),
                'Project_DeletedAt'     => null, // Optional
            ]);
        }
    }
}
