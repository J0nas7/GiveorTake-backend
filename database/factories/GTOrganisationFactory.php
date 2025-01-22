<?php

namespace Database\Factories;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GTOrganisationFactory extends Factory
{
    protected $model = Organisation::class;

    public function definition()
    {
        return [
            'User_ID'                   => User::factory()->new(), // Generate a user if one doesn't exist
            'Organisation_Name'         => $this->faker->company, // Random company name
            'Organisation_Description'  => $this->faker->sentence, // Random description
            'Organisation_CreatedAt'    => now(),
            'Organisation_UpdatedAt'    => now(),
            'Organisation_DeletedAt'    => null, // Optional
        ];
    }
}
