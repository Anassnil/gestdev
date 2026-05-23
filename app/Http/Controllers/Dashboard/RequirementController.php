<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Requirement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequirementController extends Controller
{
    // List requirements for a board (used by PlanningToolsController)
    public function store(Board $board, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!is_string($value)) return;
                    $wordCount = str_word_count(trim(strip_tags($value)));
                    if ($wordCount > 500) {
                        $fail('The description may not be greater than 500 words.');
                    }
                },
            ],
            'type' => 'required|string|max:32',
            'priority' => 'nullable|string',
            'status' => 'nullable|string',
            'estimate' => 'nullable|integer',
            'acceptance_criteria' => 'nullable|string',
            'parent_epic' => 'nullable|exists:requirements,id',
        ]);

        // normalize tags which may come as tags[] or comma CSV
        $tags = [];
        if($request->has('tags')){
            $raw = $request->input('tags');
            if(is_array($raw)) $tags = $raw;
            elseif(is_string($raw)) $tags = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        $requirement = Requirement::create([
            'board_id' => $board->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => $validated['status'] ?? 'draft',
            'tags' => $tags,
            'parent_id' => $validated['parent_epic'] ?? null,
            'estimate' => $validated['estimate'] ?? null,
            'acceptance_criteria' => $validated['acceptance_criteria'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        if($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json'){
            return response()->json(['requirement' => $requirement->fresh()], 201);
        }
        return redirect()->back()->with('status','Requirement created');
    }

    public function update(Board $board, Requirement $requirement, Request $request)
    {
        if($requirement->board_id !== $board->id) abort(404);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!is_string($value)) return;
                    $wordCount = str_word_count(trim(strip_tags($value)));
                    if ($wordCount > 500) {
                        $fail('The description may not be greater than 500 words.');
                    }
                },
            ],
            'type' => 'sometimes|required|string|max:32',
            'priority' => 'nullable|string',
            'status' => 'nullable|string',
            'tags' => 'nullable',
            'estimate' => 'nullable|integer',
            'acceptance_criteria' => 'nullable|string',
        ]);

        if($request->has('tags')){
            $raw = $request->input('tags');
            if(is_string($raw)) $validated['tags'] = array_values(array_filter(array_map('trim', explode(',', $raw))));
            elseif(is_array($raw)) $validated['tags'] = $raw;
        }

        $requirement->update($validated);
        return response()->json(['requirement' => $requirement->fresh()]);
    }

    public function destroy(Board $board, Requirement $requirement)
    {
        if($requirement->board_id !== $board->id) abort(404);
        $requirement->delete();
        return response('',204);
    }

    public function move(Board $board, Requirement $requirement, Request $request)
    {
        if($requirement->board_id !== $board->id) abort(404);
        $data = $request->validate(['type' => 'required|string|max:32','position' => 'nullable|integer']);
        $requirement->update(['type' => $data['type'], 'position' => $data['position'] ?? 0]);
        return response()->json(['requirement' => $requirement->fresh()]);
    }

    public function attachTask(Board $board, Requirement $requirement, Request $request)
    {
        if($requirement->board_id !== $board->id) abort(404);
        $data = $request->validate(['task_id' => 'required|exists:tasks,id']);
        $requirement->tasks()->syncWithoutDetaching([$data['task_id']]);
        return response()->json(['requirement' => $requirement->load('tasks')]);
    }

    public function detachTask(Board $board, Requirement $requirement, $taskId)
    {
        if($requirement->board_id !== $board->id) abort(404);
        $requirement->tasks()->detach($taskId);
        return response('',204);
    }

    public function exportPdf(Board $board)
    {
        $board->load(['requirements.tasks']);
        $requirements = $board->requirements->sortBy('position')->values();

        $generatedAt = now();
        $pdf = Pdf::loadView('dashboard.planning.tools.requirements_pdf', [
            'board' => $board,
            'requirements' => $requirements,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4');

        $safeBoardName = preg_replace('/[^A-Za-z0-9\-]+/', '-', strtolower($board->name ?: 'board'));
        $fileName = 'project-specification-' . $safeBoardName . '-' . $generatedAt->format('Ymd-His') . '.pdf';
        $directory = 'specifications/board-' . $board->id;
        $relativePath = $directory . '/' . $fileName;

        Storage::disk('public')->put($relativePath, $pdf->output());

        return response()->download(storage_path('app/public/' . $relativePath), $fileName);
    }
}
