<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use App\Models\TaskMediaFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class GTTaskCommentFactory extends Factory
{
    protected $model = TaskComment::class;

    public function definition()
    {
        return [
            'Task_ID'        => Task::factory(),
            'User_ID'        => User::factory(),
            'Comment_Text'   => $this->faker->paragraph,
            'Comment_CreatedAt' => now(),
            'Comment_UpdatedAt' => now(),
        ];
    }
}

class GTTaskMediaFileFactory extends Factory
{
    protected $model = TaskMediaFile::class;

    public function definition()
    {
        return [
            'Task_ID'        => Task::factory(),
            'Uploaded_By_User_ID' => User::factory(),
            'Media_File_Name' => $this->faker->word . '.' . $this->faker->fileExtension,
            'Media_File_Path' => $this->faker->filePath(),
            'Media_File_Type' => $this->faker->randomElement(['image/png', 'image/jpeg', 'video/mp4']),
            'Media_CreatedAt' => now(),
            'Media_UpdatedAt' => now(),
        ];
    }
}
?>