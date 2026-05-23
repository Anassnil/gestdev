<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Roadmap;
use App\Models\Milestone;
use Illuminate\Http\Request;

class RoadmapController extends Controller
{
    public function index(Board $board)
    {
        $roadmaps = Roadmap::where('board_id', $board->id)->with('milestones')->get();
        return view('dashboard.planning.roadmaps.index', compact('board','roadmaps'));
    }

    public function create(Board $board)
    {
        return view('dashboard.planning.roadmaps.create', compact('board'));
    }

    public function store(Board $board, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $data['board_id'] = $board->id;
        $roadmap = Roadmap::create($data);
        return redirect()->route('dashboard.planning.roadmaps.show', [$board, $roadmap]);
    }

    public function show(Board $board, Roadmap $roadmap)
    {
        $roadmap->load('milestones');
        return view('dashboard.planning.roadmaps.show', compact('board','roadmap'));
    }

    public function destroy(Board $board, Roadmap $roadmap)
    {
        $roadmap->delete();
        return redirect()->route('dashboard.planning.roadmaps.index', $board);
    }

    // Milestones
    public function storeMilestone(Board $board, Roadmap $roadmap, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'due_at' => 'nullable|date',
        ]);
        $data['roadmap_id'] = $roadmap->id;
        $data['position'] = $roadmap->milestones()->count();
        Milestone::create($data);
        return back();
    }

    public function updateMilestone(Board $board, Roadmap $roadmap, Milestone $milestone, Request $request)
    {
        // Allow partial updates (toggle completed without sending title)
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'notes' => 'nullable|string',
            'due_at' => 'nullable|date',
            'completed' => 'nullable|boolean',
        ]);

        // Ensure completed is properly cast when present
        if ($request->has('completed')) {
            $data['completed'] = (bool) $request->input('completed');
        }

        $milestone->update($data);

        // Redirect to the roadmap show page to refresh computed progress
        return redirect()->route('dashboard.planning.roadmaps.show', [$board, $roadmap]);
    }

    public function destroyMilestone(Board $board, Roadmap $roadmap, Milestone $milestone)
    {
        $milestone->delete();
        return back();
    }
}
