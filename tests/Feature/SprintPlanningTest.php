<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Board;
use App\Models\Task;
use App\Models\User;

class SprintPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sprint_and_assign_task()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['board_id' => $board->id, 'points' => 5]);

        $resp = $this->post(route('dashboard.planning.sprints.store', $board), ['title' => 'Sprint Alpha', 'start_at' => now()->toDateString(), 'end_at' => now()->addDays(7)->toDateString(), 'capacity_points' => 40]);
        $resp->assertRedirect(route('dashboard.planning.sprint_planning', $board));

        $sprint = $board->sprints()->first();
        $this->assertNotNull($sprint);

        $assign = $this->putJson(route('dashboard.planning.tasks.assignSprint', [$board, $task]), ['sprint_id' => $sprint->id, 'status' => 'todo', 'position' => 1]);
        $assign->assertStatus(200)->assertJson(['ok' => true]);

        $task->refresh();
        $this->assertEquals($sprint->id, $task->sprint_id);
        $this->assertEquals(1, $task->position);
    }

    public function test_move_task_back_to_backlog_and_reorder()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['board_id' => $board->id, 'points' => 3]);
        $sprint = $board->sprints()->create(['title' => 'S1']);

        $this->putJson(route('dashboard.planning.tasks.assignSprint', [$board, $task]), ['sprint_id' => $sprint->id, 'status' => 'todo', 'position' => 1])->assertStatus(200);

        // move back to backlog (null sprint)
        $this->putJson(route('dashboard.planning.tasks.assignSprint', [$board, $task]), ['sprint_id' => null, 'status' => 'todo', 'position' => 2])->assertStatus(200);
        $task->refresh();
        $this->assertNull($task->sprint_id);
    }

    public function test_assign_task_to_developer_updates_workload()
    {
        $user = User::factory()->create();
        $dev = User::factory()->create();
        $this->actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['board_id' => $board->id, 'points' => 8]);
        $sprint = $board->sprints()->create(['title' => 'S2']);

        $resp = $this->putJson(route('dashboard.planning.tasks.assignSprint', [$board, $task]), ['sprint_id' => $sprint->id, 'status' => 'todo', 'assignee_id' => $dev->id]);
        $resp->assertStatus(200)->assertJsonPath('task.assignee_id', $dev->id);
        $this->assertEquals(8, $resp->json('sprintPoints'));
    }
}
