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
        // Collect auth/access info for debugging and rendering
        $user = auth()->user();
        $canAccess = $user ? $board->canAccess($user) : false;


        // If the request expects JSON, still enforce access
        if(request()->wantsJson() && (! $canAccess)){
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Load diagrams regardless for the page render; view will show debug info if access is blocked
        $diagrams = Diagram::with(['creator','updater'])->where('board_id', $board->id)->orderByDesc('created_at')->get();

        $aiModels = AIModel::all();
        if(request()->wantsJson()){
            return response()->json(['ok' => true, 'diagrams' => $diagrams, 'ai_models' => $aiModels]);
        }

        return view('dashboard.planning.diagrams.index', compact('board','diagrams','aiModels','user','canAccess'));
    }

    public function store(Request $request, Board $board)
    {
        if (! auth()->user() || ! $board->canEdit(auth()->user())) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'type' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:5120',
            'pdf' => 'nullable|file|mimes:pdf|max:5120'
        ]);

        $imgPath = null; $pdfPath = null;
        if($request->hasFile('image')){
            $imgPath = $request->file('image')->store('diagrams', 'public');
        }
        if($request->hasFile('pdf')){
            $pdfPath = $request->file('pdf')->store('diagrams', 'public');
        }

        // Idempotency guard: if a diagram with the same title was created very
        // recently for this board, return it instead of creating a duplicate.
        $recentWindow = now()->subSeconds(5);
        $existing = Diagram::where('board_id', $board->id)
            ->where('title', $data['title'])
            ->where('created_at', '>=', $recentWindow)
            ->first();

        if ($existing) {
            return response()->json(['ok' => true, 'diagram' => $existing, 'notice' => 'Duplicate prevented'], 200);
        }

        $diagram = Diagram::create([
            'board_id' => $board->id,
            'type' => $data['type'] ?? 'uml',
            'title' => $data['title'],
            'image' => $imgPath,
            'pdf' => $pdfPath,
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'diagram' => $diagram]);
    }

    public function update(Request $request, Board $board, Diagram $diagram)
    {
        if($diagram->board_id !== $board->id) return response()->json(['error'=>'Not found'],404);
        if (! auth()->user() || ! $board->canEdit(auth()->user())) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'type' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:5120',
            'pdf' => 'nullable|file|mimes:pdf|max:5120'
        ]);
        if($request->hasFile('image')){
            if($diagram->image) Storage::disk('public')->delete($diagram->image);
            $diagram->image = $request->file('image')->store('diagrams','public');
        }
        if($request->hasFile('pdf')){
            if($diagram->pdf) Storage::disk('public')->delete($diagram->pdf);
            $diagram->pdf = $request->file('pdf')->store('diagrams','public');
        }
        if(isset($data['title'])) $diagram->title = $data['title'];
        if(isset($data['type'])) $diagram->type = $data['type'];
        if(array_key_exists('code',$data)) $diagram->code = $data['code'];
        if(array_key_exists('description',$data)) $diagram->description = $data['description'];
        $diagram->updated_by = auth()->id();
        $diagram->save();
        return response()->json(['ok' => true, 'diagram' => $diagram]);
    }

    public function destroy(Board $board, Diagram $diagram)
    {
        if($diagram->board_id !== $board->id) return response()->json(['error'=>'Not found'],404);
        if (! auth()->user() || ! $board->canEdit(auth()->user())) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if($diagram->image) Storage::disk('public')->delete($diagram->image);
        if($diagram->pdf) Storage::disk('public')->delete($diagram->pdf);
        $diagram->delete();
        return response()->json(['ok' => true]);
    }
}
