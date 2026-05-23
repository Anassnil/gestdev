<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Diagram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AIModel;

class DiagramHubController extends Controller
{
    public function index(Board $board)
    {
        $diagrams = Diagram::where('board_id', $board->id)->orderByDesc('created_at')->get();
        $aiModels = AIModel::all();
        if(request()->wantsJson()){
            return response()->json(['ok' => true, 'diagrams' => $diagrams, 'ai_models' => $aiModels]);
        }

        return view('dashboard.planning.diagrams.index', compact('board','diagrams','aiModels'));
    }

    public function store(Request $request, Board $board)
    {
        $data = $request->validate([
            'type' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'code' => 'nullable|string',
            'image' => 'nullable|image|max:5120'
        ]);

        $path = null;
        if($request->hasFile('image')){
            $path = $request->file('image')->store('diagrams', 'public');
        }

        $diagram = Diagram::create([
            'board_id' => $board->id,
            'type' => $data['type'] ?? 'uml',
            'title' => $data['title'],
            'image' => $path,
            'code' => $data['code'] ?? null,
        ]);

        return response()->json(['ok' => true, 'diagram' => $diagram]);
    }

    public function update(Request $request, Board $board, Diagram $diagram)
    {
        if($diagram->board_id !== $board->id) return response()->json(['error'=>'Not found'],404);
        $data = $request->validate(['type' => 'nullable|string|max:50','title' => 'nullable|string|max:255','code' => 'nullable|string','image' => 'nullable|image|max:5120']);
        if($request->hasFile('image')){
            // delete old image
            if($diagram->image) Storage::disk('public')->delete($diagram->image);
            $diagram->image = $request->file('image')->store('diagrams','public');
        }
        if(isset($data['title'])) $diagram->title = $data['title'];
        if(isset($data['type'])) $diagram->type = $data['type'];
        if(array_key_exists('code',$data)) $diagram->code = $data['code'];
        $diagram->save();
        return response()->json(['ok' => true, 'diagram' => $diagram]);
    }

    public function destroy(Board $board, Diagram $diagram)
    {
        if($diagram->board_id !== $board->id) return response()->json(['error'=>'Not found'],404);
        if($diagram->image) Storage::disk('public')->delete($diagram->image);
        $diagram->delete();
        return response()->json(['ok' => true]);
    }
}
