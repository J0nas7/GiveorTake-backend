<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;

class GTTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create 5 sample teams
        Team::factory()->count(5)->create();
    }
}
?>