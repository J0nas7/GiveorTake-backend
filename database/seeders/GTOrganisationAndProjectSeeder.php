<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Seeder;

class GTOrganisationAndProjectSeeder extends Seeder
{
    public function run()
    {
        // Use the correct factory name
        // Create 10 organisations, each with 3 teams with a project each
        /*Organisation::factory()->new()
            ->count(10)
            ->has(
                Team::factory()->new()
                    ->count(3)
                    ->has(
                        Project::factory()->new()
                            ->count(1), 'projects'
                    ), 'teams'
            )
            ->create();*/
    }
}
