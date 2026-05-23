<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AIPlanningService;
use App\Models\AIPlan;

class AIPlanningWebController extends Controller
{
    protected $service;

    public function __construct(AIPlanningService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $plans = AIPlan::where('user_id', auth()->id())->orderByDesc('created_at')->paginate(20);
        return view('ai_plans.index', compact('plans'));
    }

    public function create()
    {
        return view('ai_plans.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['idea' => 'required|string', 'title' => 'nullable|string', 'board_id' => 'nullable|integer']);
        $result = $this->service->analyzeIdea($data['idea'], ['board_id' => $data['board_id'] ?? null, 'title' => $data['title'] ?? null]);
        // Fetch the plan just created for this user (booted() auto-fills user_id on create).
        $plan = AIPlan::where('user_id', auth()->id())->orderByDesc('created_at')->first();

        $parsed = $this->service->isValidSchema($result);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'plan_id' => $plan->id, 'parsed' => $parsed, 'result' => $result]);
        }

        if (!$parsed) {
            session()->flash('ai_plan_warning', 'The AI response could not be parsed into structured JSON; the textual output was saved under Architecture Overview.');
        }

        return redirect()->route('ai.plans.show', $plan->id);
    }

    public function show(AIPlan $plan)
    {
        if ($plan->user_id && $plan->user_id !== auth()->id()) {
            abort(403);
        }
        return view('ai_plans.show', compact('plan'));
    }
}
