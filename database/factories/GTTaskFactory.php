<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GTTaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition()
    {
        return [
            'Project_ID'        => Project::factory(),
            'Team_ID'           => Team::factory()->optional(),
            'Assigned_User_ID'  => User::factory()->optional(),
            'Task_Title'        => $this->faker->sentence(6),
            'Task_Description'  => $this->faker->paragraph,
            'Task_Status'       => $this->faker->randomElement(['To Do', 'In Progress', 'Review', 'Done']),
            'Task_Due_Date'     => $this->faker->optional()->date(),
            'Task_CreatedAt'    => now(),
            'Task_UpdatedAt'    => now(),
            'Task_DeletedAt'      => null, // Soft delete support
        ];
    }

    public function fromDemoData(array $taskData)
    {
        return $this->state(function () use ($taskData) {
            return [
                'Project_ID'        => Project::inRandomOrder()->first()->Project_ID ?? Project::factory(),
                'Team_ID'           => Team::inRandomOrder()->first()->Team_ID ?? null,
                'Assigned_User_ID'  => $taskData['Assigned_User_ID'] ?? null,
                'Task_Title'        => $taskData['Task_Title'],
                'Task_Description'  => $taskData['Task_Description'] ?? null,
                'Task_Status'       => $taskData['Task_Status'],
                'Task_Due_Date'     => $taskData['Task_Due_Date'] ?? null,
                'Task_CreatedAt'    => $taskData['Task_CreatedAt'],
                'Task_UpdatedAt'    => now(),
            ];
        });
    }
}
?>