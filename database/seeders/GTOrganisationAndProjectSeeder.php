<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\Project;
use Illuminate\Database\Seeder;

class GTOrganisationAndProjectSeeder extends Seeder
{
    public function run()
    {
        // Use the correct factory name
        // Create 10 organisations, each with 5 projects
        Organisation::factory()->new()
            ->count(10)
            ->has(
                Project::factory()->new()
                    ->count(5), 'projects'
            )
            ->create();
    }
}
