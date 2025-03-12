<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class GTProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition()
    {
        return [
            'Team_ID'               => Team::factory()->new(), // Generate an organisation if one doesn't exist
            'Project_Name'          => $this->faker->sentence(3), // Random project name
            'Project_Key'           => strtoupper($this->faker->unique()->bothify('??###')), // Random project key // lexify('?????')),  // Only letters
            'Project_Description'   => $this->faker->paragraph, // Random project description
            'Project_Status'        => $this->faker->randomElement(['Planned', 'Active', 'Completed', 'On Hold']),
            'Project_Start_Date'    => $this->faker->date,
            'Project_End_Date'      => $this->faker->date,
            'Project_CreatedAt'     => now(),
            'Project_UpdatedAt'     => now(),
            'Project_DeletedAt'     => null, // Optional
        ];
    }
}
