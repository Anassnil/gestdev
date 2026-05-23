<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\AIPlanningService;

class PlanningToolsController extends Controller
{
    public function roadmap(Board $board)
    {
        return view('dashboard.planning.tools.roadmap', compact('board'));
    }

    public function sprint_planning(Board $board)
    {
        $board->load(['tasks.assignee','sprints.tasks.assignee']);
        // developers involved on this board
        $devIds = $board->tasks()->whereNotNull('assignee_id')->pluck('assignee_id')->unique()->filter();
        $developers = \App\Models\User::whereIn('id', $devIds)->get();
        return view('dashboard.planning.tools.sprint_planning', compact('board','developers'));
    }

    public function backlog_grooming(Board $board)
    {
        return view('dashboard.planning.tools.backlog_grooming', compact('board'));
    }

    public function sprint_board(Board $board)
    {
        $board = Board::with(['tasks.assignee', 'sprints'])->findOrFail($board->id);
        // developers involved on this board
        $devIds = $board->tasks()->whereNotNull('assignee_id')->pluck('assignee_id')->unique()->filter();
        $developers = \App\Models\User::whereIn('id', $devIds)->get();
        $sprints = $board->sprints;
        return view('dashboard.planning.tools.sprint_board', compact('board','developers','sprints'));
    }

    public function retrospective(Board $board)
    {
        $board->load(['tasks.assignee','sprints']);
        $devIds = $board->tasks()->whereNotNull('assignee_id')->pluck('assignee_id')->unique()->filter();
        $developers = \App\Models\User::whereIn('id', $devIds)->get();
        $sprints = $board->sprints;
        return view('dashboard.planning.tools.retrospective', compact('board','developers','sprints'));
    }

    public function release(Board $board)
    {
        $board->load(['tasks.assignee','sprints','releases','requirements']);
        $releases = $board->releases;
        $requirements = $board->requirements ?? collect();
        return view('dashboard.planning.tools.release', compact('board','releases','requirements'));
    }

    public function requirements(Board $board)
    {
        $board->load(['tasks.assignee','sprints','releases','requirements']);
        $requirements = $board->requirements ?? collect();
        return view('dashboard.planning.tools.requirements', compact('board','requirements'));
    }

    public function projectTracking(Board $board)
    {
        // Load tasks ordered by newest first so latest appear on top
        $board->load(['sprints']);
        $taskModels = $board->tasks()->with('assignee')->orderByDesc('created_at')->get();
        $tasks = $taskModels->map(function($t){
            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'assignee' => $t->assignee ? $t->assignee->name : null,
                'status' => $t->status,
                'pr_url' => $t->pr_url ?? null,
                'created_at' => $t->created_at ? $t->created_at->toDateTimeString() : null,
            ];
        });
        return view('dashboard.planning.tools.project_tracking', compact('board','tasks'));
    }

    public function syncGit(Board $board)
    {
        // Mark tasks with a PR url as in_progress
        $updated = [];
        foreach($board->tasks as $t){
            if($t->pr_url && $t->status === 'todo'){
                $t->status = 'in_progress';
                $t->save();
                $updated[] = $t;
            }
        }
        $payload = $board->tasks->map(function($t){ return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'assignee' => $t->assignee ? $t->assignee->name : null,
            'status' => $t->status,
            'pr_url' => $t->pr_url ?? null,
        ]; });
        return response()->json(['ok' => true, 'tasks' => $payload]);
    }

    public function aiAssist(Request $request, Board $board)
    {
        // Generate a more technical, structured suggestion using board context
        $boardLabel = $board->name ? (' for ' . Str::limit($board->name, 32)) : '';

        // Collect up to 5 outstanding todo tasks as context
        $todos = $board->tasks()->where('status', 'todo')->orderBy('position')->limit(5)->get();
        $context = [];
        foreach($todos as $t){
            $context[] = trim($t->title);
        }

        $baseTopic = $request->input('topic', $board->name ?? 'Project');

        // Build structured suggestion: title, description, acceptance criteria, steps, estimate
        $title = 'AI Suggestion: Implement ' . Str::limit($baseTopic, 60) . $boardLabel;

        $descriptionLines = [];
        $descriptionLines[] = "Summary: Perform a technical implementation or refinement related to '" . Str::limit($baseTopic, 120) . "'.";
        if(count($context)){
            $descriptionLines[] = "Context: recent todo items — " . implode('; ', $context);
        }

        $descriptionLines[] = "\nAcceptance Criteria:\n- Code builds without errors\n- Unit tests added or updated covering new behavior\n- Documentation updated with configuration or usage notes";

        $steps = [
            '1) Create or update API/DB contracts required for the feature',
            '2) Implement backend logic with clear input/output and validations',
            '3) Add unit and integration tests (focus on edge cases)',
            '4) Create minimal frontend components or API clients if applicable',
            '5) Add deployment/CI checks and performance regression tests'
        ];

        $estimate = rand(2,16); // hours estimate (rough)

        $description = implode("\n", $descriptionLines) . "\n\nProposed Steps:\n- " . implode("\n- ", $steps) . "\n\nEstimated effort: ~" . $estimate . "h" . "\n\nGenerated at " . Carbon::now()->toDateTimeString();

        // Avoid creating exact duplicate suggestions
        $latestAi = $board->tasks()->where('title', 'like', 'AI Suggestion:%')->orderByDesc('created_at')->first();
        if($latestAi && $latestAi->description === $description){
            $task = $latestAi;
        } else {
            $task = $board->tasks()->create([
                'title' => $title,
                'description' => $description,
                'position' => ($board->tasks()->max('position') ?? 0) + 1,
                'status' => 'todo',
            ]);
        }

        $payload = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee' => null,
            'status' => $task->status,
            'pr_url' => $task->pr_url ?? null,
        ];

        return response()->json(['ok' => true, 'task' => $payload]);
    }

    /**
     * Generate multiple suggestion options (no DB write).
     * Delegates entirely to AIPlanningService (never calls LLM directly).
     */
    public function generateAiSuggestions(Request $request, Board $board)
    {
        $prompt = trim($request->input('prompt', ''));
        $todos = $board->tasks()->where('status', 'todo')->orderBy('position')->limit(5)->pluck('title')->toArray();

        $service = app(AIPlanningService::class);
        $suggestions = $service->generateTaskSuggestions($prompt, $todos, [
            'temperature' => (float) $request->input('temperature', config('services.llm.temperature', 0.6)),
        ]);

        return response()->json(['ok' => true, 'suggestions' => $suggestions]);
    }

    /**
     * Accept a selected suggestion and create the task.
     */
    public function selectAiSuggestion(Request $request, Board $board)
    {
        $data = $request->only(['title','description']);
        $title = trim($data['title'] ?? 'AI Suggestion');
        $description = trim($data['description'] ?? 'Auto-generated suggestion');

        // Optional suggestion key to prevent duplicates
        $key = trim($request->input('key', ''));

        if($key){
            // If a task already exists with this key in the description, return it
            $existing = $board->tasks()->where('description', 'like', "%{$key}%")->orderByDesc('created_at')->first();
            if($existing){
                $payload = [
                    'id' => $existing->id,
                    'title' => $existing->title,
                    'description' => $existing->description,
                    'assignee' => null,
                    'status' => $existing->status,
                    'pr_url' => $existing->pr_url ?? null,
                ];
                return response()->json(['ok' => true, 'task' => $payload]);
            }
        }

        // Also avoid exact duplicate title+description
        $existingExact = $board->tasks()->where('title', $title)->where('description', $description)->first();
        if($existingExact){
            $payload = [
                'id' => $existingExact->id,
                'title' => $existingExact->title,
                'description' => $existingExact->description,
                'assignee' => null,
                'status' => $existingExact->status,
                'pr_url' => $existingExact->pr_url ?? null,
            ];
            return response()->json(['ok' => true, 'task' => $payload]);
        }

        // Append the key to the description for traceability (if provided)
        if($key && strpos($description, $key) === false){
            $description .= "\n\nSuggestion: {$key}";
        }

        $task = $board->tasks()->create([
            'title' => $title,
            'description' => $description,
            'position' => ($board->tasks()->max('position') ?? 0) + 1,
            'status' => 'todo',
        ]);

        $payload = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee' => null,
            'status' => $task->status,
            'pr_url' => $task->pr_url ?? null,
        ];

        return response()->json(['ok' => true, 'task' => $payload]);
    }

    /**
     * Server-side search endpoint for tasks. Supports q, type, page, per_page.
     */
    public function searchTasks(Request $request, Board $board)
    {
        $q = trim($request->query('q', ''));
        $type = $request->query('type', 'full');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(200, max(10, (int) $request->query('per_page', 50)));

        $query = $board->tasks()->with('assignee');

        if ($q !== '') {
            switch ($type) {
                case 'title':
                    $query->where('title', 'like', "%{$q}%");
                    break;
                case 'description':
                    $query->where('description', 'like', "%{$q}%");
                    break;
                case 'assignee':
                    $query->whereHas('assignee', function($qb) use ($q) { $qb->where('name', 'like', "%{$q}%"); });
                    break;
                case 'status':
                    $query->where('status', 'like', "%{$q}%");
                    break;
                case 'has_pr':
                    $query->whereNotNull('pr_url')->where('pr_url', '!=', '');
                    break;
                case 'unassigned':
                    $query->whereNull('assignee_id');
                    break;
                case 'full':
                default:
                    $query->where(function($qb) use ($q) {
                        $qb->where('title', 'like', "%{$q}%")
                           ->orWhere('description', 'like', "%{$q}%")
                           ->orWhere('status', 'like', "%{$q}%");
                    });
                    break;
            }
        }

        $total = $query->count();
        $tasks = $query->orderByDesc('created_at')->skip(($page-1)*$perPage)->take($perPage)->get();

        $payload = $tasks->map(function($t){
            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'assignee' => $t->assignee ? $t->assignee->name : null,
                'status' => $t->status,
                'pr_url' => $t->pr_url ?? null,
                'created_at' => $t->created_at ? $t->created_at->toDateTimeString() : null,
            ];
        });

        return response()->json(['ok' => true, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'tasks' => $payload]);
    }

    
}
