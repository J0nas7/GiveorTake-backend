<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;

class GTTeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition()
    {
        return [
            'Organisation_ID'  => Organisation::factory(), // Generate or use an existing organisation
            'Team_Name'        => $this->faker->company . ' Team', // Random team name
            'Team_Description' => $this->faker->sentence(10), // Random description
            'Team_CreatedAt'   => now(),
            'Team_UpdatedAt'   => now(),
        ];
    }
}
?>