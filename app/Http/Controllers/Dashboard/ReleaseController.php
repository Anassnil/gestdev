<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Release;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function store(Board $board, Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:32',
            'version' => 'nullable|string|max:64',
            'target_date' => 'nullable|date',
            'priority' => 'nullable|string',
        ]);
        $data['board_id'] = $board->id;
        $release = Release::create($data);
        return response()->json(['release' => $release], 201);
    }

    public function update(Board $board, Release $release, Request $request)
    {
        if($release->board_id !== $board->id){ abort(404); }
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|max:32',
            'version' => 'nullable|string|max:64',
            'target_date' => 'nullable|date',
            'priority' => 'nullable|string',
            'status' => 'nullable|string'
        ]);
        $release->update($data);
        return response()->json(['release' => $release]);
    }

    public function destroy(Board $board, Release $release)
    {
        if($release->board_id !== $board->id){ abort(404); }
        $release->delete();
        return response('', 204);
    }

    public function move(Board $board, Release $release, Request $request)
    {
        if($release->board_id !== $board->id){ abort(404); }
        $data = $request->validate([
            'type' => 'required|string|max:32',
            'position' => 'nullable|integer'
        ]);
        $release->update([ 'type' => $data['type'], 'position' => $data['position'] ?? 0 ]);
        return response()->json(['release' => $release]);
    }
}
