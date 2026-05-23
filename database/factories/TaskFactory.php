<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => 'open',
            'position' => 0,
            'assignee_id' => null,
            'priority' => 'medium',
            'points' => 0,
            'type' => 'task',
            'due_date' => null,
            'dependencies' => null,
            'tags' => null,
            'sprint_id' => null,
            'quadrant' => null,
        ];
    }
}
