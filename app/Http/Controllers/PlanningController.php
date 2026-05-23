<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Task;
use App\Models\BoardCollaborator;
use Illuminate\Http\Request;

class PlanningController extends Controller
{
    public function index()
    {
        $userId = request()->user()?->id;

        $boards = Board::withCount('tasks')
            ->where('user_id', $userId)
            ->get();

        // Boards shared with this user by others
        $sharedBoards = Board::withCount('tasks')
            ->whereHas('collaborators', fn($q) => $q->where('user_id', $userId))
            ->with('user')
            ->get()
            ->map(function ($b) use ($userId) {
                $b->pivot_role = $b->collaborators->where('user_id', $userId)->first()?->role;
                return $b;
            });

        return view('dashboard.planning.index', compact('boards', 'sharedBoards'));
    }

    public function show(Board $board)
    {
        $board->load(['tasks', 'collaboratorUsers', 'user']);
        $columns = [
            'todo' => 'To do',
            'in_progress' => 'In Progress',
            'done' => 'Done',
        ];

        $grouped = [];
        foreach ($columns as $key => $label) {
            $grouped[$key] = $board->tasks->where('status', $key)->sortBy('position');
        }

        return view('dashboard.planning.show', compact('board','grouped','columns'));
    }

    public function storeBoard(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $board = Board::create(array_merge($data, ['user_id' => $request->user()?->id]));
        return redirect()->route('dashboard.planning.show', $board);
    }

    public function updateBoard(Request $request, Board $board)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $board->update($data);
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'board' => $board]);
        }
        return redirect()->route('dashboard.planning.index');
    }

    public function destroyBoard(Request $request, Board $board)
    {
        $board->delete();
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('dashboard.planning.index');
    }

    public function storeTask(Request $request, Board $board)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points' => 'nullable|numeric',
            'priority' => 'nullable|string',
            'sprint_id' => 'nullable|exists:sprints,id',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $position = $board->tasks()->where('status', 'todo')->max('position') ?? 0;
        $task = $board->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'position' => $position + 1,
            'points' => $data['points'] ?? null,
            'priority' => $data['priority'] ?? null,
            'sprint_id' => $data['sprint_id'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
        ]);
        if ($request->wantsJson() || $request->ajax()) {
            $task->load('assignee');
            return response()->json(['ok' => true, 'task' => $task]);
        }
        return redirect()->back();
    }

    public function moveTask(Request $request, Task $task)
    {
        $ownerId = $task->board?->user_id;
        if ((int) $ownerId !== (int) $request->user()?->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate(['status' => 'required|in:todo,in_progress,done', 'position' => 'nullable|integer']);
        $task->status = $data['status'];
        if (isset($data['position'])) {
            $task->position = (int) $data['position'];
        }
        $task->save();
        return response()->json(['ok' => true, 'task' => $task]);
    }

    public function updateTask(Request $request, Board $board, Task $task)
    {
        if ($task->board_id !== $board->id) {
            return response()->json(['error' => 'Task does not belong to board'], 403);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'points' => 'nullable|numeric',
            'priority' => 'nullable|string',
            'sprint_id' => 'nullable|exists:sprints,id',
            'assignee_id' => 'nullable|exists:users,id',
            'assignee_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:todo,in_progress,done',
            'position' => 'nullable|integer',
            'pr_url' => 'nullable|url'
        ]);

        $task->fill($data);
        $task->save();
        $task->load('assignee');
        return response()->json(['ok' => true, 'task' => $task]);
    }

    public function setAi(Request $request, Board $board)
    {
        $data = $request->validate(['ai_enabled' => 'required|boolean']);
        $board->ai_enabled = (bool) $data['ai_enabled'];
        $board->save();
        return response()->json(['ok' => true, 'ai_enabled' => $board->ai_enabled]);
    }

    public function destroyTask(Board $board, Task $task)
    {
        if ($task->board_id !== $board->id) {
            return response()->json(['error' => 'Task does not belong to board'], 403);
        }
        $task->delete();
        return response()->json(['ok' => true]);
    }
}
