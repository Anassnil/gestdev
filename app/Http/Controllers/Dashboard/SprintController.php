<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function store(Board $board, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'capacity_points' => 'nullable|integer',
        ]);

        $sprint = $board->sprints()->create($data);
        return redirect()->route('dashboard.planning.sprint_planning', $board)->with('status', 'Sprint created');
    }

    public function assignTask(Board $board, Task $task, Request $request)
    {
        $data = $request->validate([
            'sprint_id' => 'nullable|exists:sprints,id',
            'status' => 'nullable|in:todo,in_progress,done',
            'position' => 'nullable|integer',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        // assign sprint (nullable to move back to backlog)
        $task->sprint_id = $data['sprint_id'] ?? null;

        if (isset($data['status'])) {
            $task->status = $data['status'];
        }

        if (isset($data['assignee_id'])) {
            $task->assignee_id = $data['assignee_id'];
        }

        // position handling: if provided, apply; otherwise, append to end
        if (isset($data['position'])) {
            $task->position = (int) $data['position'];
        } else {
            $max = $board->tasks()->where('sprint_id', $task->sprint_id)->max('position') ?? 0;
            $task->position = $max + 1;
        }

        $task->save();

        // compute updated sprint totals
        $sprintPoints = 0;
        $sprintCapacity = null;
        if ($task->sprint_id) {
            $s = Sprint::find($task->sprint_id);
            $sprintPoints = $s->tasks()->sum('points');
            $sprintCapacity = $s->capacity_points;
        }

        // developer workload for the board (simple sum of assigned task points)
        $devWork = [];
        $devs = $board->tasks()->whereNotNull('assignee_id')->pluck('assignee_id')->unique();
        foreach($devs as $devId) {
            $devWork[$devId] = Task::where('assignee_id', $devId)->where('sprint_id', $task->sprint_id)->sum('points');
        }

        return response()->json(['ok' => true, 'task' => $task, 'sprintPoints' => $sprintPoints, 'sprintCapacity' => $sprintCapacity, 'devWork' => $devWork]);
    }
}
