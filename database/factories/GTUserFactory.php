<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GTUserFactory extends Factory
{
    // Specify the model associated with this factory
    protected $model = \App\Models\User::class;
    
    public function definition()
    {
        return [
            'User_Status'           => $this->faker->numberBetween(0, 1),
            'User_Email'            => $this->faker->unique()->safeEmail,
            'User_Password'         => bcrypt('password'), // Use bcrypt for hashed passwords
            'User_Remember_Token'   => Str::random(10),
            'User_FirstName'        => $this->faker->firstName, // Generate a random first name
            'User_Surname'          => $this->faker->lastName, // Generate a random last name
            'User_ImageSrc'         => $this->faker->imageUrl(640, 480, 'people', true),
            'User_CreatedAt'        => now(),
            'User_UpdatedAt'        => now(),
            'User_DeletedAt'        => null, // Optional
        ];
    }
}