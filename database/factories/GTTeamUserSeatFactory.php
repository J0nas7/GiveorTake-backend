<?php

namespace Database\Factories;

use App\Models\TeamUserSeat;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamUserSeatFactory extends Factory
{
    protected $model = TeamUserSeat::class;

    public function definition(): array
    {
        return [
            'Team_ID' => Team::factory(), // Creates a new Team for each seat
            'User_ID' => User::factory(), // Creates a new User for each seat
            'Seat_Role' => $this->faker->word(), // Random role
            'Seat_Status' => $this->faker->randomElement(['Active', 'Inactive', 'Pending']),
            'Seat_Role_Description' => $this->faker->sentence(),
            'Seat_Permissions' => json_encode(['view', 'edit']), // Random permissions
            'Seat_Expiration' => $this->faker->optional()->dateTime(),
        ];
    }
}
?>