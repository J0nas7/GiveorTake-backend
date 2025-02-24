<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TeamUserSeat;

class TeamUserSeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TeamUserSeat::factory()->count(10)->create(); // Creates 10 team-user seat records
    }
}
?>